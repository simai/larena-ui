<?php

declare(strict_types=1);

namespace Larena\Ui\Frontend;

use Larena\Ui\Contracts\UiAssetGraph;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Enums\UiAssetKind;
use RuntimeException;

final readonly class FrontendRuntimeAssetResolver
{
    private function __construct(private FrontendRuntimeLock $lock) {}

    public static function bundled(): self
    {
        return new self(FrontendRuntimeLock::bundled());
    }

    public static function coreGraph(): UiAssetGraph
    {
        return new UiAssetGraph([
            new UiAssetRequirement('simai.framework.core.css', UiAssetKind::Css, true),
            new UiAssetRequirement('simai.framework.core.js', UiAssetKind::JavaScript, true),
        ], [
            'runtime:simai-framework',
            'delivery:core-boot',
            'delivery:pinned-immutable-pair',
        ]);
    }

    /**
     * @return list<array{asset_key:string,kind:string,relative_path:string,critical:bool}>
     */
    public function resolve(UiAssetGraph $graph): array
    {
        if (!$graph->isValid()) {
            throw new RuntimeException('ui_frontend_runtime_asset_graph_invalid');
        }

        $assets = [];
        foreach ($graph->requirements as $requirement) {
            $asset = $this->resolveRequirement($requirement);
            $assets[$asset['asset_key']] = $asset;
        }

        return array_values($assets);
    }

    /** @return list<string> */
    public function preloadedCssPaths(UiAssetGraph $graph): array
    {
        return array_values(array_map(
            static fn (array $asset): string => $asset['relative_path'],
            array_filter($this->resolve($graph), static fn (array $asset): bool => $asset['kind'] === 'css'),
        ));
    }

    /** @return array{asset_key:string,kind:string,relative_path:string,critical:bool} */
    private function resolveRequirement(UiAssetRequirement $requirement): array
    {
        $paths = [
            'simai.framework.core.css' => (string) $this->lock->toArray()['boot']['css'],
            'simai.framework.core.js' => (string) $this->lock->toArray()['boot']['javascript'],
            'simai.framework.smart_base.js' => (string) $this->lock->toArray()['boot']['smart_base'],
            'simai.framework.bridge.js' => 'resources/js/sf-runtime-bridge.js',
            'simai.framework.sf_button.js' => (string) $this->lock->component('sf-button')['javascript'],
            'simai.framework.sf_table.css' => (string) $this->lock->component('sf-table')['css'],
            'simai.framework.sf_table.js' => (string) $this->lock->component('sf-table')['javascript'],
        ];
        $relativePath = $paths[$requirement->assetKey] ?? null;
        if (!is_string($relativePath) || $relativePath === '') {
            throw new RuntimeException('ui_frontend_runtime_asset_unknown:' . $requirement->assetKey);
        }

        return [
            'asset_key' => $requirement->assetKey,
            'kind' => $requirement->kind->value,
            'relative_path' => $relativePath,
            'critical' => $requirement->critical,
        ];
    }
}
