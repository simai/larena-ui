<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use InvalidArgumentException;
use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Contracts\SmartBackendRenderer;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Enums\RenderStrategy;

final class CompositeBackendRenderer implements SmartBackendRenderer
{
    public function render(SmartComponentManifest $manifest, array $props, array $slots = []): BackendRenderResult
    {
        $container = $manifest->constraints['container'] ?? null;
        $cssClass = $manifest->constraints['css_class'] ?? null;
        $slotOrder = $manifest->constraints['slot_order'] ?? null;
        if (!is_string($container) || !in_array($container, ['details', 'div', 'section', 'nav'], true)
            || !is_string($cssClass) || preg_match('/^[a-z][a-z0-9_-]{0,79}$/', $cssClass) !== 1
            || !is_array($slotOrder) || !array_is_list($slotOrder)) {
            throw new InvalidArgumentException('ui_smart_composite_template_invalid:' . $manifest->componentKey);
        }

        $ordered = [];
        foreach ($slotOrder as $slot) {
            if (!is_string($slot) || !in_array($slot, $manifest->slotKeys, true) || isset($ordered[$slot])) {
                throw new InvalidArgumentException('ui_smart_composite_slot_order_invalid:' . $manifest->componentKey);
            }
            $ordered[$slot] = true;
        }
        if (array_keys($ordered) !== $manifest->slotKeys) {
            throw new InvalidArgumentException('ui_smart_composite_slot_order_incomplete:' . $manifest->componentKey);
        }

        $expanded = $container === 'details' && ($props['expanded'] ?? false) === true;
        $html = '<' . $container . ' class="' . self::escape($cssClass) . '" data-larena-composite="'
            . self::escape($manifest->componentKey) . '"' . ($expanded ? ' open' : '') . '>';
        $title = $props['title'] ?? null;
        if (is_string($title) && trim($title) !== '') {
            $titleTag = $container === 'details' ? 'summary' : 'h1';
            $html .= '<' . $titleTag . ' class="' . self::escape($cssClass . '__title') . '">' . self::escape($title) . '</' . $titleTag . '>';
        }
        foreach (array_keys($ordered) as $slot) {
            $html .= '<div class="' . self::escape($cssClass . '__' . str_replace('.', '-', $slot))
                . '" data-larena-slot="' . self::escape($slot) . '">' . ($slots[$slot] ?? '') . '</div>';
        }
        $html .= '</' . $container . '>';

        return new BackendRenderResult(
            $html,
            RenderStrategy::Native,
            HydrationContract::none(),
            $manifest->assetRequirements,
        );
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
