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
        $paginationNextHref = null;
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
            if ($key === 'next-href' && $tag === 'sf-pagination') {
                if (!is_string($value) || preg_match('#^/[A-Za-z0-9_./?=&%:+-]{1,2047}$#', $value) !== 1) {
                    throw new \InvalidArgumentException('ui_smart_pagination_next_href_invalid');
                }
                $paginationNextHref = $value;
                continue;
            }
            if ($key === 'items' && $tag === 'sf-admin-menu') {
                $children = self::adminMenuItems($value);
                continue;
            }
            if ($key === 'items' && $tag === 'sf-breadcrumbs') {
                $attributes['items'] = self::breadcrumbsItems($value);
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

        if ($tag === 'sf-pagination' && $paginationNextHref !== null) {
            $label = $props['show-more-text'] ?? 'Show more';
            if (!is_string($label) || trim($label) === '') {
                throw new \InvalidArgumentException('ui_smart_pagination_next_label_invalid');
            }
            $children = '<a class="larena-pagination-next" data-larena-pagination-next href="'
                . self::escape($paginationNextHref) . '">' . self::escape($label) . '</a>';
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
            || ($tag === 'sf-dropdown' && $attribute === 'search')
            || ($tag === 'sf-admin-menu' && $attribute === 'settings')
            || ($tag === 'sf-tag' && $attribute === 'closable');
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

    private static function adminMenuItems(mixed $items, int $depth = 0): string
    {
        if (!is_array($items) || !array_is_list($items) || count($items) > 40 || $depth > 2) {
            throw new \InvalidArgumentException('ui_smart_admin_menu_items_invalid');
        }

        $html = '';
        $allowed = ['label', 'href', 'left-icon', 'badge', 'active', 'disabled', 'slot', 'type', 'children'];
        foreach ($items as $item) {
            if (!is_array($item) || array_is_list($item)) {
                throw new \InvalidArgumentException('ui_smart_admin_menu_item_invalid');
            }
            foreach (array_keys($item) as $key) {
                if (!in_array($key, $allowed, true)) {
                    throw new \InvalidArgumentException('ui_smart_admin_menu_item_prop_unknown:' . (string) $key);
                }
            }
            $type = $item['type'] ?? 'item';
            if (!is_string($type) || !in_array($type, ['item', 'divider'], true)) {
                throw new \InvalidArgumentException('ui_smart_admin_menu_item_type_invalid');
            }
            $attributes = [];
            if ($type === 'divider') {
                $attributes['type'] = 'divider';
            } else {
                $label = $item['label'] ?? null;
                if (!is_string($label) || trim($label) === '' || mb_strlen($label) > 100) {
                    throw new \InvalidArgumentException('ui_smart_admin_menu_item_label_invalid');
                }
                $attributes['label'] = $label;
                foreach (['left-icon', 'badge'] as $key) {
                    $value = $item[$key] ?? null;
                    if ($value !== null) {
                        if (!is_string($value) || trim($value) === '' || strlen($value) > 80
                            || preg_match('/^[A-Za-z0-9 _.-]+$/', $value) !== 1) {
                            throw new \InvalidArgumentException('ui_smart_admin_menu_item_value_invalid:' . $key);
                        }
                        $attributes[$key] = $value;
                    }
                }
                $href = $item['href'] ?? null;
                if ($href !== null) {
                    if (!is_string($href) || preg_match('#^/[A-Za-z0-9_./?=&%:+-]{0,2047}$#', $href) !== 1) {
                        throw new \InvalidArgumentException('ui_smart_admin_menu_item_href_invalid');
                    }
                    $attributes['href'] = $href;
                }
                foreach (['active', 'disabled'] as $key) {
                    if (array_key_exists($key, $item)) {
                        if (!is_bool($item[$key])) {
                            throw new \InvalidArgumentException('ui_smart_admin_menu_item_boolean_invalid:' . $key);
                        }
                        if ($item[$key]) {
                            $attributes[$key] = '';
                        }
                    }
                }
                if (isset($item['slot'])) {
                    if (!is_string($item['slot']) || !in_array($item['slot'], ['main', 'bottom'], true)) {
                        throw new \InvalidArgumentException('ui_smart_admin_menu_item_slot_invalid');
                    }
                    $attributes['slot'] = $item['slot'];
                }
            }
            $children = isset($item['children']) ? self::adminMenuItems($item['children'], $depth + 1) : '';
            $html .= '<sf-admin-menu-item' . self::attributes($attributes) . '>' . $children . '</sf-admin-menu-item>';
        }

        return $html;
    }

    private static function breadcrumbsItems(mixed $items): string
    {
        if (!is_array($items) || !array_is_list($items) || $items === [] || count($items) > 12) {
            throw new \InvalidArgumentException('ui_smart_breadcrumb_items_invalid');
        }
        $safe = [];
        foreach ($items as $item) {
            if (!is_array($item) || array_is_list($item)) {
                throw new \InvalidArgumentException('ui_smart_breadcrumb_item_invalid');
            }
            foreach (array_keys($item) as $key) {
                if (!in_array($key, ['label', 'href', 'icon', 'current'], true)) {
                    throw new \InvalidArgumentException('ui_smart_breadcrumb_item_prop_unknown:' . (string) $key);
                }
            }
            $label = $item['label'] ?? null;
            if (!is_string($label) || trim($label) === '' || mb_strlen($label) > 100) {
                throw new \InvalidArgumentException('ui_smart_breadcrumb_item_label_invalid');
            }
            $entry = ['label' => $label];
            if (isset($item['href'])) {
                if (!is_string($item['href']) || preg_match('#^/[A-Za-z0-9_./?=&%:+-]{0,2047}$#', $item['href']) !== 1) {
                    throw new \InvalidArgumentException('ui_smart_breadcrumb_item_href_invalid');
                }
                $entry['href'] = $item['href'];
            }
            if (isset($item['icon'])) {
                if (!is_string($item['icon']) || preg_match('/^[A-Za-z0-9_.-]{1,80}$/', $item['icon']) !== 1) {
                    throw new \InvalidArgumentException('ui_smart_breadcrumb_item_icon_invalid');
                }
                $entry['icon'] = $item['icon'];
            }
            if (isset($item['current'])) {
                if (!is_bool($item['current'])) {
                    throw new \InvalidArgumentException('ui_smart_breadcrumb_item_current_invalid');
                }
                $entry['current'] = $item['current'];
            }
            $safe[] = $entry;
        }

        return json_encode($safe, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
