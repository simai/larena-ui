<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use InvalidArgumentException;
use Larena\Ui\Contracts\FrontendRenderArtifact;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\UiAssetGraph;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Frontend\FrontendRuntimeAssetResolver;
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Registry\SmartRegistry;
use Larena\Ui\Validation\SmartPropsValidator;

final readonly class SmartManager
{
    private SmartPropsValidator $propsValidator;

    public function __construct(private SmartRegistry $registry, ?SmartPropsValidator $propsValidator = null)
    {
        $this->propsValidator = $propsValidator ?? new SmartPropsValidator();
    }

    public static function withDefaults(): self
    {
        return new self(SmartRegistry::withDefaults());
    }

    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $assetActivation
     * @param array<string, string> $slots
     */
    public function render(string $key, array $props, array $assetActivation, array $slots = []): FrontendRenderArtifact
    {
        $manifest = $this->registry->manifest($key);
        $this->propsValidator->validate($manifest, $props);
        $this->assertSlots($manifest, $slots);
        $render = $this->registry->renderer($manifest->rendererId)->render($manifest, $props, $slots);
        $graph = new UiAssetGraph($render->assetRequirements, [
            'smart-component:' . $manifest->componentKey,
            'smart-manifest-version:' . $manifest->version,
            'smart-owner:' . $manifest->ownerPackage,
            'smart-renderer:' . $manifest->rendererId,
        ]);
        $assetRequirementsSatisfied = $this->assertAssetContract($manifest, $graph, $assetActivation);

        return new FrontendRenderArtifact($render, $graph, $assetActivation, [
            'schema' => 'larena.ui.smart_render_diagnostics.v1',
            'component_key' => $manifest->componentKey,
            'manifest_version' => $manifest->version,
            'owner_package' => $manifest->ownerPackage,
            'renderer_id' => $manifest->rendererId,
            'frontend_runtime' => $manifest->frontendRuntime,
            'frontend_tag' => $manifest->frontendTag,
            'manifest_provenance' => $manifest->provenance,
            'asset_requirements_satisfied' => $assetRequirementsSatisfied,
            'asset_contract_mode' => $assetRequirementsSatisfied ? 'manifest_verified' : 'legacy_runtime_graph',
            'production_ready' => false,
            'all_41_packages_ready' => false,
        ]);
    }

    private function assertSlots(SmartComponentManifest $manifest, array $slots): void
    {
        foreach ($slots as $name => $value) {
            if (!is_string($name) || !in_array($name, $manifest->slotKeys, true) || !is_string($value)) {
                throw new InvalidArgumentException('ui_smart_slot_invalid:' . $manifest->componentKey . ':' . (string) $name);
            }
        }
    }

    /** @param array<string, mixed> $assetActivation */
    private function assertAssetContract(SmartComponentManifest $manifest, UiAssetGraph $graph, array $assetActivation): bool
    {
        if (!$graph->isValid()) {
            throw new InvalidArgumentException('ui_smart_asset_graph_invalid:' . $manifest->componentKey);
        }
        if ($manifest->assetRequirements === []) {
            return false;
        }
        $manifestKeys = array_map(static fn (UiAssetRequirement $asset): string => $asset->assetKey, $manifest->assetRequirements);
        $renderKeys = array_map(static fn (UiAssetRequirement $asset): string => $asset->assetKey, $graph->requirements);
        sort($manifestKeys);
        sort($renderKeys);
        if ($manifestKeys !== $renderKeys) {
            throw new InvalidArgumentException('ui_smart_asset_manifest_mismatch:' . $manifest->componentKey);
        }
        $runtimeRequirements = array_filter(
            $graph->requirements,
            static fn (UiAssetRequirement $asset): bool => str_starts_with($asset->assetKey, 'simai.framework.'),
        );
        if ($runtimeRequirements !== []) {
            if (($assetActivation['runtime_pair'] ?? null) !== FrontendRuntimeLock::bundled()->pairId()) {
                throw new InvalidArgumentException('ui_smart_asset_runtime_pair_missing:' . $manifest->componentKey);
            }
            FrontendRuntimeAssetResolver::bundled()->resolve(new UiAssetGraph(
                array_values($runtimeRequirements),
                ['smart-component:' . $manifest->componentKey, 'delivery:pinned-immutable-pair'],
            ));
        }

        return true;
    }
}
