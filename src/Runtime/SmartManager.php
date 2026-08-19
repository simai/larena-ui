<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use InvalidArgumentException;
use Larena\Ui\Contracts\FrontendRenderArtifact;
use Larena\Ui\Contracts\BackendRenderResult;
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

    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $assetActivation
     * @param array<string, string> $slots
     * @param list<string> $modifiers
     * @param array<string, array<string, mixed>> $childProps
     */
    public function renderView(
        string $key,
        string $viewKey,
        array $props,
        array $assetActivation,
        array $slots = [],
        ?string $preset = null,
        array $modifiers = [],
        array $childProps = [],
    ): FrontendRenderArtifact {
        return $this->renderViewAtDepth($key, $viewKey, $props, $assetActivation, $slots, $preset, $modifiers, $childProps, 0, []);
    }

    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $assetActivation
     * @param array<string, string> $slots
     * @param list<string> $modifiers
     * @param array<string, array<string, mixed>> $childProps
     * @param list<string> $stack
     */
    private function renderViewAtDepth(
        string $key,
        string $viewKey,
        array $props,
        array $assetActivation,
        array $slots,
        ?string $preset,
        array $modifiers,
        array $childProps,
        int $depth,
        array $stack,
    ): FrontendRenderArtifact {
        if ($depth > 6) {
            throw new InvalidArgumentException('ui_smart_view_composition_depth_exceeded');
        }
        $viewIdentity = $key . ':' . $viewKey;
        if (in_array($viewIdentity, $stack, true)) {
            throw new InvalidArgumentException('ui_smart_view_composition_cycle:' . $viewIdentity);
        }
        $stack[] = $viewIdentity;
        $view = $this->registry->view($key, $viewKey);
        $resolved = $view->resolve($props, $preset, $modifiers);
        foreach ($childProps as $childId => $override) {
            if (!self::isChildPropsEntry($childId, $override) || !isset($resolved['children'][$childId])) {
                throw new InvalidArgumentException('ui_smart_view_child_props_invalid:' . $viewIdentity . ':' . (string) $childId);
            }
        }

        $childArtifacts = [];
        foreach ($resolved['children'] as $childId => $child) {
            [$overrideProps, $nestedChildProps] = self::splitChildProps($childProps[$childId] ?? []);
            $artifact = $this->renderViewAtDepth(
                $child['component'],
                $child['view'],
                array_replace_recursive($child['props'], $overrideProps),
                $assetActivation,
                [],
                $child['preset'],
                $child['modifiers'],
                $nestedChildProps,
                $depth + 1,
                $stack,
            );
            $childArtifacts[$childId] = $artifact;
            $slot = $child['slot'];
            $slots[$slot] = ($slots[$slot] ?? '') . $artifact->html();
        }

        $parent = $this->render($key, $resolved['props'], $assetActivation, $slots);
        if ($childArtifacts === []) {
            return $parent;
        }

        $requirements = $parent->render->assetRequirements;
        $seen = [];
        foreach ($requirements as $requirement) {
            $seen[$requirement->assetKey . ':' . $requirement->kind->value] = true;
        }
        foreach ($childArtifacts as $artifact) {
            foreach ($artifact->render->assetRequirements as $requirement) {
                $assetIdentity = $requirement->assetKey . ':' . $requirement->kind->value;
                if (!isset($seen[$assetIdentity])) {
                    $requirements[] = $requirement;
                    $seen[$assetIdentity] = true;
                }
            }
        }
        $render = new BackendRenderResult(
            $parent->render->html,
            $parent->render->strategy,
            $parent->render->hydration,
            $requirements,
            $parent->render->copiedFrontendSource,
        );
        $graph = new UiAssetGraph($requirements, [
            ...$parent->assetGraph->explain,
            'smart-view:' . $viewIdentity,
            'composite-children:' . count($childArtifacts),
        ]);

        return new FrontendRenderArtifact($render, $graph, $assetActivation, [
            ...$parent->diagnostics,
            'view_key' => $viewKey,
            'view_template' => $resolved['template'],
            'composite_child_count' => count($childArtifacts),
        ]);
    }

    private static function isChildPropsEntry(mixed $childId, mixed $override): bool
    {
        if (!is_string($childId) || !is_array($override) || array_is_list($override)) {
            return false;
        }
        if (!array_key_exists('_props', $override) && !array_key_exists('_children', $override)) {
            return true;
        }
        foreach (array_keys($override) as $key) {
            if (!in_array($key, ['_props', '_children'], true)) {
                return false;
            }
        }
        if (isset($override['_props']) && (!is_array($override['_props']) || array_is_list($override['_props']))) {
            return false;
        }
        if (isset($override['_children']) && (!is_array($override['_children']) || array_is_list($override['_children']))) {
            return false;
        }

        return true;
    }

    /** @return array{array<string, mixed>, array<string, array<string, mixed>>} */
    private static function splitChildProps(array $override): array
    {
        if (!array_key_exists('_props', $override) && !array_key_exists('_children', $override)) {
            return [$override, []];
        }

        return [
            is_array($override['_props'] ?? null) ? $override['_props'] : [],
            is_array($override['_children'] ?? null) ? $override['_children'] : [],
        ];
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
