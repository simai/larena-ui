<?php

declare(strict_types=1);

namespace Larena\Ui\Validation;

use InvalidArgumentException;
use Larena\Ui\Contracts\SmartComponentManifest;

final class SmartPropsValidator
{
    /** @param array<string, mixed> $props */
    public function validate(SmartComponentManifest $manifest, array $props): void
    {
        $schema = $manifest->propsSchema;
        if (($schema['type'] ?? null) !== 'object' || !is_array($schema['properties'] ?? null)) {
            throw new InvalidArgumentException('ui_smart_props_schema_invalid:' . $manifest->componentKey);
        }
        $properties = $schema['properties'];
        foreach ($props as $name => $value) {
            if (!isset($properties[$name]) || !is_array($properties[$name])) {
                throw new InvalidArgumentException('ui_smart_prop_unknown:' . $manifest->componentKey . ':' . $name);
            }
            $property = $properties[$name];
            $this->assertType($manifest->componentKey, $name, $value, (string) ($property['type'] ?? ''));
            $enum = $property['enum'] ?? null;
            if (is_array($enum) && !in_array($value, $enum, true)) {
                throw new InvalidArgumentException('ui_smart_prop_enum_invalid:' . $manifest->componentKey . ':' . $name);
            }
            if (is_string($value)) {
                $this->assertStringRules($manifest->componentKey, $name, $value, $property);
            }
        }
        foreach (is_array($schema['required'] ?? null) ? $schema['required'] : [] as $required) {
            if (!is_string($required) || !array_key_exists($required, $props)) {
                throw new InvalidArgumentException('ui_smart_prop_required:' . $manifest->componentKey . ':' . (string) $required);
            }
        }

        $this->assertConstraints($manifest, $props);
    }

    /** @param array<string, mixed> $property */
    private function assertStringRules(string $component, string $name, string $value, array $property): void
    {
        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        foreach (['minLength', 'maxLength'] as $constraint) {
            if (isset($property[$constraint]) && (!is_int($property[$constraint]) || $property[$constraint] < 0)) {
                throw new InvalidArgumentException('ui_smart_props_schema_invalid:' . $component);
            }
        }
        if (isset($property['minLength']) && $length < $property['minLength']) {
            throw new InvalidArgumentException('ui_smart_prop_min_length_invalid:' . $component . ':' . $name);
        }
        if (isset($property['maxLength']) && $length > $property['maxLength']) {
            throw new InvalidArgumentException('ui_smart_prop_max_length_invalid:' . $component . ':' . $name);
        }
        if (isset($property['pattern'])) {
            if (!is_string($property['pattern']) || $property['pattern'] === '') {
                throw new InvalidArgumentException('ui_smart_props_schema_invalid:' . $component);
            }
            $matched = @preg_match('~' . str_replace('~', '\\~', $property['pattern']) . '~u', $value);
            if ($matched === false) {
                throw new InvalidArgumentException('ui_smart_props_schema_invalid:' . $component);
            }
            if ($matched !== 1) {
                throw new InvalidArgumentException('ui_smart_prop_pattern_invalid:' . $component . ':' . $name);
            }
        }
    }

    /** @param array<string, mixed> $props */
    private function assertConstraints(SmartComponentManifest $manifest, array $props): void
    {
        $combinations = $manifest->constraints['allowed_combinations'] ?? [];
        if (!is_array($combinations)) {
            throw new InvalidArgumentException('ui_smart_constraints_invalid:' . $manifest->componentKey);
        }
        foreach ($combinations as $combination) {
            $keys = is_array($combination) ? ($combination['keys'] ?? null) : null;
            $values = is_array($combination) ? ($combination['values'] ?? null) : null;
            if (!is_array($keys) || !array_is_list($keys) || $keys === [] || !is_array($values) || !array_is_list($values)) {
                throw new InvalidArgumentException('ui_smart_constraints_invalid:' . $manifest->componentKey);
            }
            foreach ($keys as $constraintKey) {
                if (!is_string($constraintKey)) {
                    throw new InvalidArgumentException('ui_smart_constraints_invalid:' . $manifest->componentKey);
                }
            }
            $actual = [];
            foreach ($keys as $constraintKey) {
                if (!array_key_exists($constraintKey, $props)) {
                    continue 2;
                }
                $actual[] = $props[$constraintKey];
            }
            $matched = false;
            foreach ($values as $allowed) {
                if (is_array($allowed) && array_is_list($allowed) && count($allowed) === count($keys) && $allowed === $actual) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                throw new InvalidArgumentException('ui_smart_constraint_combination_invalid:' . $manifest->componentKey . ':' . implode(',', $keys));
            }
        }

        $requirements = $manifest->constraints['requires'] ?? [];
        if (!is_array($requirements)) {
            throw new InvalidArgumentException('ui_smart_constraints_invalid:' . $manifest->componentKey);
        }
        foreach ($requirements as $requirement) {
            $when = is_array($requirement) && is_array($requirement['when'] ?? null) ? $requirement['when'] : null;
            $then = is_array($requirement) && is_array($requirement['then'] ?? null) ? $requirement['then'] : null;
            if ($when === null || $when === [] || $then === null || $then === []) {
                throw new InvalidArgumentException('ui_smart_constraints_invalid:' . $manifest->componentKey);
            }
            foreach ($when as $name => $value) {
                if (!is_string($name) || !array_key_exists($name, $props) || $props[$name] !== $value) {
                    continue 2;
                }
            }
            foreach ($then as $name => $value) {
                if (!is_string($name) || !array_key_exists($name, $props) || $props[$name] !== $value) {
                    throw new InvalidArgumentException('ui_smart_constraint_requirement_invalid:' . $manifest->componentKey . ':' . (string) $name);
                }
            }
        }
    }

    private function assertType(string $component, string $name, mixed $value, string $type): void
    {
        $valid = match ($type) {
            'string' => is_string($value),
            'boolean' => is_bool($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'array' => is_array($value) && array_is_list($value),
            'object' => is_array($value),
            default => false,
        };
        if (!$valid) {
            throw new InvalidArgumentException('ui_smart_prop_type_invalid:' . $component . ':' . $name . ':' . $type);
        }
    }
}
