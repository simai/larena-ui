<?php

declare(strict_types=1);

namespace Larena\Ui;

use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Contracts\UiAssetGraph;
use Larena\Ui\Enums\HydrationStrategy;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Enums\UiAssetKind;
use Larena\Ui\Frontend\SourceBackedComponentRegistry;

/** Laravel-native entry point for pinned SIMAI Framework smart elements. */
final class Smart
{
    /** @param array<string, mixed> $props */
    public static function render(string $tag, array $props = [], ?SourceBackedComponentRegistry $registry = null): BackendRenderResult
    {
        $registry ??= SourceBackedComponentRegistry::bundled();
        $registry->assertPropsAllowed($tag, $props);

        $hash = hash('sha256', json_encode($props, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $payload = null;
        $id = isset($props['id']) && is_string($props['id']) && preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $props['id'])
            ? $props['id']
            : ($tag === 'sf-table' ? 'larena-smart-' . substr($hash, 0, 12) : null);
        $attributes = ['data-larena-smart-runtime' => $registry->pairId()];
        if ($id !== null) {
            $attributes = ['id' => $id] + $attributes;
        }
        foreach ($props as $key => $value) {
            if ($key === 'data' && $tag === 'sf-table') {
                $payload = $value;
                continue;
            }
            if (is_bool($value)) {
                if ($value) {
                    $attributes[$key] = '';
                }
                continue;
            }
            if (!is_scalar($value)) {
                throw new \InvalidArgumentException('ui_smart_attribute_must_be_scalar:' . $tag . ':' . $key);
            }
            $attributes[$key] = (string) $value;
        }

        $html = '<' . $tag . self::attributes($attributes) . '></' . $tag . '>';
        if ($payload !== null && $id !== null) {
            if (!is_array($payload)) {
                throw new \InvalidArgumentException('ui_smart_table_data_must_be_array');
            }
            $json = json_encode(
                ['target' => $id, 'component' => $tag, 'props' => ['data' => $payload]],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
            );
            $html .= '<script type="application/json" data-larena-smart-hydration="' . self::escape($id) . '">' . $json . '</script>';
        }

        return new BackendRenderResult(
            $html,
            RenderStrategy::Host,
            new HydrationContract(HydrationStrategy::Adopt, $hash, 'stable-host', true),
            self::assetGraph($tag)->requirements,
        );
    }

    public static function assetGraph(string $tag): UiAssetGraph
    {
        $requirements = [
            new UiAssetRequirement('simai.framework.core.css', UiAssetKind::Css, true),
            new UiAssetRequirement('simai.framework.core.js', UiAssetKind::JavaScript, true),
            new UiAssetRequirement('simai.framework.smart_base.js', UiAssetKind::JavaScript, true),
            new UiAssetRequirement('simai.framework.bridge.js', UiAssetKind::JavaScript, true),
        ];
        if ($tag === 'sf-button') {
            $requirements[] = new UiAssetRequirement('simai.framework.sf_button.js', UiAssetKind::JavaScript, true);
        }
        if ($tag === 'sf-table') {
            $requirements[] = new UiAssetRequirement('simai.framework.sf_table.css', UiAssetKind::Css, true);
            $requirements[] = new UiAssetRequirement('simai.framework.sf_table.js', UiAssetKind::JavaScript, true);
        }

        return new UiAssetGraph($requirements, [
            'runtime:simai-framework',
            'smart-component:' . $tag,
            'delivery:pinned-immutable-pair',
        ]);
    }

    /** @param array<string, string> $attributes */
    private static function attributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            $html .= ' ' . self::escape($key);
            if ($value !== '') {
                $html .= '="' . self::escape($value) . '"';
            }
        }
        return $html;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
