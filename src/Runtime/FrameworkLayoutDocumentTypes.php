<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use Closure;
use InvalidArgumentException;

/** Backend projection of the selected Framework page/section types. */
final class FrameworkLayoutDocumentTypes
{
    /** @return array<string,array{validate:Closure,render:Closure}> */
    public static function registrations(): array
    {
        $types = [];
        foreach (['layout.page', 'layout.section'] as $type) {
            $types[$type] = ['validate' => static function (array $node) use ($type): void { self::validate($node, $type); },
                'render' => static function (array $node, array $slots) use ($type): string {
                    self::validate($node, $type);
                    $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $tag = $type === 'layout.page' ? 'main' : 'section';
                    $attributes = ' data-sf-composition-id="'.$escape($node['id']).'"';
                    if ($type === 'layout.section') $attributes .= ' data-composition-preset="'.$escape($node['presentation']['preset'] ?? 'surface').'"';
                    return '<'.$tag.$attributes.'>'.($slots['default'] ?? '').'</'.$tag.'>';
                }];
        }
        return $types;
    }

    /** @param array<string,mixed> $node */
    private static function validate(array $node, string $type): void
    {
        $slots = $node['slots'] ?? [];
        $presentation = $node['presentation'] ?? [];
        if (($node['type'] ?? null) !== $type || !is_string($node['id'] ?? null)
            || ($node['data'] ?? []) !== [] || ($node['props'] ?? []) !== []
            || !is_array($slots) || array_diff(array_keys($slots), ['default']) !== []
            || (isset($slots['default']) && (!is_array($slots['default']) || !array_is_list($slots['default']) || count($slots['default']) > 500))
            || !is_array($presentation) || array_diff(array_keys($presentation), ['view','preset','modifiers']) !== []
            || (isset($presentation['view']) && $presentation['view'] !== 'default')) {
            throw new InvalidArgumentException('ui_document_layout_type_invalid');
        }
        $presets = $type === 'layout.section' ? ['surface', 'contrast'] : [];
        $modifiers = $type === 'layout.section' ? ['contained', 'spacious'] : [];
        if (isset($presentation['preset']) && !in_array($presentation['preset'], $presets, true)) throw new InvalidArgumentException('ui_document_layout_preset_invalid');
        if (isset($presentation['modifiers']) && (!is_array($presentation['modifiers']) || !array_is_list($presentation['modifiers'])
            || array_diff($presentation['modifiers'], $modifiers) !== [])) throw new InvalidArgumentException('ui_document_layout_modifier_invalid');
    }
}
