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

/** Laravel-native entry point for pinned Simai Framework smart elements. */
final class Smart
{
    /** @param array<string, mixed> $props */
    public static function render(string $tag, array $props = [], ?SourceBackedComponentRegistry $registry = null): BackendRenderResult
    {
        $registry ??= SourceBackedComponentRegistry::bundled();
        $registry->assertPropsAllowed($tag, $props);

        $hash = hash('sha256', json_encode($props, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $payload = null;
        $children = '';
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
            if ($key === 'options' && $tag === 'sf-dropdown') {
                $children = self::dropdownOptions($value);
                continue;
            }
            if (is_bool($value)) {
                if ($value) {
                    $attributes[$key] = '';
                } elseif (self::requiresExplicitFalse($tag, (string) $key)) {
                    $attributes[$key] = 'false';
                }
                continue;
            }
            if (!is_scalar($value)) {
                throw new \InvalidArgumentException('ui_smart_attribute_must_be_scalar:' . $tag . ':' . $key);
            }
            $attributes[$key] = (string) $value;
        }

        $html = '<' . $tag . self::attributes($attributes) . '>' . $children . '</' . $tag . '>';
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
            self::assetGraph($tag, $registry)->requirements,
        );
    }

    public static function assetGraph(string $tag, ?SourceBackedComponentRegistry $registry = null): UiAssetGraph
    {
        $registry ??= SourceBackedComponentRegistry::bundled();
        $definition = $registry->get($tag);
        $requirements = [
            new UiAssetRequirement('simai.framework.core.css', UiAssetKind::Css, true),
            new UiAssetRequirement('simai.framework.core.js', UiAssetKind::JavaScript, true),
            new UiAssetRequirement('simai.framework.smart_base.js', UiAssetKind::JavaScript, true),
            new UiAssetRequirement('simai.framework.bridge.js', UiAssetKind::JavaScript, true),
        ];
        $seen = [];
        self::appendComponentRequirements($tag, $registry, $requirements, $seen);

        return new UiAssetGraph($requirements, [
            'runtime:simai-framework',
            'smart-component:' . $tag,
            'delivery:pinned-immutable-pair',
        ]);
    }

    /**
     * @param list<UiAssetRequirement> $requirements
     * @param array<string, true> $seen
     */
    private static function appendComponentRequirements(
        string $tag,
        SourceBackedComponentRegistry $registry,
        array &$requirements,
        array &$seen,
    ): void {
        if (isset($seen[$tag])) {
            return;
        }
        $seen[$tag] = true;
        $definition = $registry->get($tag);
        foreach ($definition['requires'] ?? [] as $requiredTag) {
            if (!is_string($requiredTag)) {
                throw new \InvalidArgumentException('ui_smart_component_requirement_invalid:' . $tag);
            }
            self::appendComponentRequirements($requiredTag, $registry, $requirements, $seen);
        }

        $assetPrefix = 'simai.framework.' . str_replace('-', '_', $tag);
        if (is_string($definition['css'] ?? null) && $definition['css'] !== '') {
            $requirements[] = new UiAssetRequirement($assetPrefix . '.css', UiAssetKind::Css, true);
        }
        if (is_string($definition['javascript'] ?? null) && $definition['javascript'] !== '') {
            $requirements[] = new UiAssetRequirement($assetPrefix . '.js', UiAssetKind::JavaScript, true);
        }
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

    private static function requiresExplicitFalse(string $tag, string $attribute): bool
    {
        return ($tag === 'sf-table'
                && in_array($attribute, ['selectable', 'settings', 'actions'], true))
            || ($tag === 'sf-dropdown' && $attribute === 'search');
    }

    private static function dropdownOptions(mixed $options): string
    {
        if (!is_array($options) || !array_is_list($options)) {
            throw new \InvalidArgumentException('ui_smart_dropdown_options_must_be_list');
        }

        $html = '';
        $allowed = ['text', 'value', 'type', 'size', 'icon', 'selected', 'disabled', 'aria-label'];
        foreach ($options as $option) {
            if (!is_array($option) || !isset($option['text'], $option['value'])) {
                throw new \InvalidArgumentException('ui_smart_dropdown_option_invalid');
            }
            foreach (array_keys($option) as $key) {
                if (!in_array($key, $allowed, true)) {
                    throw new \InvalidArgumentException('ui_smart_dropdown_option_prop_unknown:' . (string) $key);
                }
            }
            $attributes = [];
            foreach ($option as $key => $value) {
                if (is_bool($value)) {
                    if ($value) {
                        $attributes[$key] = '';
                    }
                    continue;
                }
                if (!is_scalar($value)) {
                    throw new \InvalidArgumentException('ui_smart_dropdown_option_attribute_must_be_scalar:' . (string) $key);
                }
                $attributes[$key] = (string) $value;
            }
            $html .= '<sf-list-item' . self::attributes($attributes) . '></sf-list-item>';
        }

        return $html;
    }
}
