<?php

declare(strict_types=1);

namespace Larena\Ui\Reference;

use InvalidArgumentException;
use Larena\Ui\Contracts\SmartComponentManifest;

final readonly class SmartComponentReference
{
    public function __construct(private SmartComponentManifest $manifest)
    {
        if (($manifest->atlas['visible'] ?? false) !== true) {
            throw new InvalidArgumentException('ui_smart_reference_not_visible:' . $manifest->componentKey);
        }

        $this->controls();
    }

    /** @return list<array<string, mixed>> */
    public function controls(): array
    {
        $controls = $this->manifest->atlas['controls'] ?? null;
        if (!is_array($controls) || !array_is_list($controls) || $controls === []) {
            throw new InvalidArgumentException('ui_smart_reference_controls_missing:' . $this->manifest->componentKey);
        }

        $normalized = [];
        $keys = [];
        foreach ($controls as $control) {
            if (!is_array($control)) {
                throw new InvalidArgumentException('ui_smart_reference_control_invalid:' . $this->manifest->componentKey);
            }
            $key = (string) ($control['key'] ?? '');
            $source = (string) ($control['source'] ?? '');
            $widget = (string) ($control['widget'] ?? '');
            if (preg_match('/^[a-z][a-z0-9_-]*$/', $key) !== 1 || isset($keys[$key])) {
                throw new InvalidArgumentException('ui_smart_reference_control_key_invalid:' . $this->manifest->componentKey);
            }
            if (!in_array($source, ['prop', 'preset'], true) || !in_array($widget, ['text', 'select', 'boolean'], true)) {
                throw new InvalidArgumentException('ui_smart_reference_control_contract_invalid:' . $this->manifest->componentKey . ':' . $key);
            }

            $options = $this->options($key, $source, $control);
            if ($widget === 'select' && $options === []) {
                throw new InvalidArgumentException('ui_smart_reference_control_options_missing:' . $this->manifest->componentKey . ':' . $key);
            }

            $keys[$key] = true;
            $normalized[] = $control + [
                'key' => $key,
                'source' => $source,
                'widget' => $widget,
                'options' => $options,
                'default' => $this->defaultValue($key, $source, $widget, $control, $options),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{controls: array<string, string|bool>, props: array<string, mixed>}
     */
    public function resolve(array $input = [], bool $submitted = false): array
    {
        $controls = $this->controls();
        $known = array_fill_keys(array_map(static fn (array $control): string => (string) $control['key'], $controls), true);
        foreach (array_keys($input) as $key) {
            if (!isset($known[$key])) {
                throw new InvalidArgumentException('ui_smart_reference_control_unknown:' . $this->manifest->componentKey . ':' . (string) $key);
            }
        }

        $props = is_array($this->manifest->atlas['example_props'] ?? null)
            ? $this->manifest->atlas['example_props']
            : [];
        $selected = [];

        foreach ($controls as $control) {
            $key = (string) $control['key'];
            $raw = array_key_exists($key, $input)
                ? $input[$key]
                : (($submitted && $control['widget'] === 'boolean') ? false : $control['default']);
            $value = $this->normalizeValue($control, $raw);
            $selected[$key] = $value;

            if ($control['source'] === 'preset') {
                $preset = $this->manifest->presets[(string) $value] ?? null;
                $presetProps = is_array($preset) && is_array($preset['props'] ?? null) ? $preset['props'] : null;
                if ($presetProps === null) {
                    throw new InvalidArgumentException('ui_smart_reference_preset_invalid:' . $this->manifest->componentKey . ':' . (string) $value);
                }
                $props = array_replace($props, $presetProps);
                continue;
            }

            if (($control['omit_empty'] ?? false) === true && $value === '') {
                unset($props[$key]);
            } else {
                $props[$key] = $value;
            }

            $mirrorProps = $control['mirror_props'] ?? [];
            if (!is_array($mirrorProps) || !array_is_list($mirrorProps)) {
                throw new InvalidArgumentException('ui_smart_reference_mirror_props_invalid:' . $this->manifest->componentKey . ':' . $key);
            }
            foreach ($mirrorProps as $mirrorProp) {
                if (!is_string($mirrorProp) || !isset($this->manifest->propsSchema['properties'][$mirrorProp])) {
                    throw new InvalidArgumentException('ui_smart_reference_mirror_prop_invalid:' . $this->manifest->componentKey . ':' . (string) $mirrorProp);
                }
                $props[$mirrorProp] = $value;
            }
        }

        foreach ($controls as $control) {
            $key = (string) $control['key'];
            $linked = $control['linked_props'] ?? null;
            if (($selected[$key] ?? false) !== true || !is_array($linked)) {
                continue;
            }
            foreach ($linked as $prop => $value) {
                if (!is_string($prop) || !isset($this->manifest->propsSchema['properties'][$prop]) || !is_scalar($value)) {
                    throw new InvalidArgumentException('ui_smart_reference_linked_prop_invalid:' . $this->manifest->componentKey . ':' . (string) $prop);
                }
                $props[$prop] = $value;
                if (isset($known[$prop])) {
                    $selected[$prop] = $value;
                }
            }
        }

        return ['controls' => $selected, 'props' => $props];
    }

    /** @param array<string, mixed> $control @return list<string> */
    private function options(string $key, string $source, array $control): array
    {
        $options = $control['options'] ?? null;
        if (is_array($options) && array_is_list($options)) {
            return array_map('strval', $options);
        }
        if ($source === 'preset') {
            return array_map('strval', array_keys($this->manifest->presets));
        }
        $property = $this->manifest->propsSchema['properties'][$key] ?? null;
        if (!is_array($property)) {
            throw new InvalidArgumentException('ui_smart_reference_prop_missing:' . $this->manifest->componentKey . ':' . $key);
        }
        $enum = $property['enum'] ?? null;

        return is_array($enum) && array_is_list($enum) ? array_map('strval', $enum) : [];
    }

    /** @param array<string, mixed> $control @param list<string> $options */
    private function defaultValue(string $key, string $source, string $widget, array $control, array $options): string|bool
    {
        if (array_key_exists('default', $control)) {
            return $widget === 'boolean' ? (bool) $control['default'] : (string) $control['default'];
        }
        if ($source === 'prop') {
            $example = $this->manifest->atlas['example_props'][$key] ?? null;
            if ($widget === 'boolean') {
                return (bool) $example;
            }
            if (is_scalar($example)) {
                return (string) $example;
            }
        }

        return $widget === 'boolean' ? false : (string) ($options[0] ?? '');
    }

    /** @param array<string, mixed> $control */
    private function normalizeValue(array $control, mixed $raw): string|bool
    {
        $key = (string) $control['key'];
        $widget = (string) $control['widget'];
        if ($raw === null && $widget === 'select' && in_array('', $control['options'], true)) {
            // Laravel's ConvertEmptyStringsToNull middleware normalizes an
            // explicitly submitted empty option before this contract runs.
            $raw = '';
        }
        if ($widget === 'boolean') {
            if (is_bool($raw)) {
                return $raw;
            }
            if (!is_scalar($raw)) {
                throw new InvalidArgumentException('ui_smart_reference_boolean_invalid:' . $this->manifest->componentKey . ':' . $key);
            }
            $value = strtolower(trim((string) $raw));
            if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($value, ['', '0', 'false', 'no', 'off'], true)) {
                return false;
            }
            throw new InvalidArgumentException('ui_smart_reference_boolean_invalid:' . $this->manifest->componentKey . ':' . $key);
        }
        if (!is_scalar($raw)) {
            throw new InvalidArgumentException('ui_smart_reference_value_invalid:' . $this->manifest->componentKey . ':' . $key);
        }
        $value = (string) $raw;
        if ($widget === 'select' && !in_array($value, $control['options'], true)) {
            throw new InvalidArgumentException('ui_smart_reference_option_invalid:' . $this->manifest->componentKey . ':' . $key);
        }
        $maxLength = max(1, min(1000, (int) ($control['max_length'] ?? 255)));
        $minLength = max(0, min($maxLength, (int) ($control['min_length'] ?? 0)));
        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        if ($widget === 'text' && ($length < $minLength || ($minLength > 0 && preg_match('/\S/u', $value) !== 1))) {
            throw new InvalidArgumentException('ui_smart_reference_text_too_short:' . $this->manifest->componentKey . ':' . $key);
        }
        if ($widget === 'text' && $length > $maxLength) {
            throw new InvalidArgumentException('ui_smart_reference_text_too_long:' . $this->manifest->componentKey . ':' . $key);
        }

        return $value;
    }
}
