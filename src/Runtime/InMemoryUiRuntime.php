<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use InvalidArgumentException;
use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\DesignPackDescriptor;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\UiAssetGraph;
use Larena\Ui\Contracts\UiResourcePackManifest;
use Larena\Ui\Enums\HydrationStrategy;

final class InMemoryUiRuntime implements \Larena\Ui\Contracts\UiRuntime
{
    /**
     * @param list<string>|null $allowedAssetKeys
     */
    public function __construct(
        private readonly ?array $allowedAssetKeys = null,
    ) {
    }

    public function validateManifest(SmartComponentManifest $manifest): bool
    {
        if (!$manifest->isValid()) {
            return false;
        }

        foreach ($manifest->assetRequirements as $requirement) {
            if (!$this->assetKeyAllowed($requirement->assetKey)) {
                return false;
            }
        }

        return true;
    }

    public function renderBackend(SmartComponentManifest $manifest, mixed $props): BackendRenderResult
    {
        if (!$this->validateManifest($manifest)) {
            throw new InvalidArgumentException('ui_runtime_invalid_or_missing_manifest');
        }

        if (!is_array($props)) {
            throw new InvalidArgumentException('ui_runtime_props_must_be_array');
        }

        $this->assertPropsMatchSchema($manifest, $props);

        $hydration = $manifest->renderStrategy->requiresFrontendRuntime()
            ? new HydrationContract(HydrationStrategy::Adopt, $this->propsHash($props), 'stable', true)
            : HydrationContract::none();

        $result = new BackendRenderResult(
            $this->renderElement($manifest, $props),
            $manifest->renderStrategy,
            $hydration,
            $manifest->assetRequirements,
            false,
        );

        if (!$result->isSafe()) {
            throw new InvalidArgumentException('ui_runtime_unsafe_backend_render_result');
        }

        return $result;
    }

    public function collectAssetGraph(SmartComponentManifest $manifest): UiAssetGraph
    {
        if (!$this->validateManifest($manifest)) {
            throw new InvalidArgumentException('ui_runtime_invalid_or_missing_manifest');
        }

        $graph = new UiAssetGraph(
            $manifest->assetRequirements,
            [
                'ui-runtime:in-memory',
                'component:' . $manifest->componentKey,
                'asset-requirements:' . count($manifest->assetRequirements),
            ],
        );

        if (!$graph->isValid()) {
            throw new InvalidArgumentException('ui_runtime_invalid_asset_graph');
        }

        return $graph;
    }

    public function validateDesignPack(DesignPackDescriptor $designPack): bool
    {
        return $designPack->isPortableDesignOnly();
    }

    public function validateResourcePack(UiResourcePackManifest $resourcePack): bool
    {
        return $resourcePack->isValid();
    }

    private function assetKeyAllowed(string $assetKey): bool
    {
        return $this->allowedAssetKeys === null || in_array($assetKey, $this->allowedAssetKeys, true);
    }

    /**
     * @param array<string, mixed> $props
     */
    private function assertPropsMatchSchema(SmartComponentManifest $manifest, array $props): void
    {
        foreach ($manifest->propsSchema as $propKey => $definition) {
            if (!is_array($definition)) {
                throw new InvalidArgumentException('ui_runtime_invalid_props_schema:' . $propKey);
            }

            $required = $definition['required'] ?? true;
            if ($required && !array_key_exists($propKey, $props)) {
                throw new InvalidArgumentException('ui_runtime_missing_required_prop:' . $propKey);
            }

            if (!array_key_exists($propKey, $props)) {
                continue;
            }

            $expectedType = $definition['type'] ?? null;
            if (is_string($expectedType) && !$this->propMatchesType($props[$propKey], $expectedType)) {
                throw new InvalidArgumentException('ui_runtime_invalid_prop_type:' . $propKey);
            }
        }
    }

    private function propMatchesType(mixed $value, string $expectedType): bool
    {
        return match ($expectedType) {
            'array', 'list' => is_array($value),
            'bool', 'boolean' => is_bool($value),
            'int', 'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'string' => is_string($value),
            default => true,
        };
    }

    /**
     * @param array<string, mixed> $props
     */
    private function renderElement(SmartComponentManifest $manifest, array $props): string
    {
        $tag = str_replace('.', '-', $manifest->componentKey);
        $attributes = [
            'data-larena-ui-runtime' => 'larena/ui:in_memory_ui_runtime',
            'data-component' => $manifest->componentKey,
        ];

        foreach ($props as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $attributes[$key] = $key;
                }

                continue;
            }

            $attributes[$key] = is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $htmlAttributes = [];
        foreach ($attributes as $key => $value) {
            $htmlAttributes[] = $this->escape($key) . '="' . $this->escape($value) . '"';
        }

        return '<' . $tag . ' ' . implode(' ', $htmlAttributes) . '></' . $tag . '>';
    }

    /**
     * @param array<string, mixed> $props
     */
    private function propsHash(array $props): string
    {
        return hash('sha256', json_encode($props, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
