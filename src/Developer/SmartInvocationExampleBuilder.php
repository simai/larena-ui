<?php

declare(strict_types=1);

namespace Larena\Ui\Developer;

use InvalidArgumentException;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Validation\SmartPropsValidator;

final class SmartInvocationExampleBuilder
{
    private SmartPropsValidator $propsValidator;

    public function __construct(?SmartPropsValidator $propsValidator = null)
    {
        $this->propsValidator = $propsValidator ?? new SmartPropsValidator();
    }

    /** @param array<string, mixed> $props */
    public function php(SmartComponentManifest $manifest, array $props): string
    {
        $this->propsValidator->validate($manifest, $props);
        $propsCode = $this->phpArray($props, 1);

        return 'use Larena\\Ui\\Facades\\Smart;' . "\n\n"
            . '$artifact = Smart::render(' . "\n"
            . '    ' . var_export($manifest->componentKey, true) . ',' . "\n"
            . $propsCode . ',' . "\n"
            . '    $assetActivation,' . "\n"
            . ');' . "\n\n"
            . '$html = $artifact->html();';
    }

    /** @param array<string, mixed> $props */
    public function frontend(SmartComponentManifest $manifest, array $props): string
    {
        $this->propsValidator->validate($manifest, $props);
        $tag = $manifest->frontendTag;
        if ($tag === null || preg_match('/^sf-[a-z0-9-]+$/', $tag) !== 1) {
            throw new InvalidArgumentException('ui_smart_reference_frontend_tag_invalid:' . $manifest->componentKey);
        }
        $attributes = [];
        foreach ($props as $key => $value) {
            if (preg_match('/^[a-z][a-z0-9-]*$/', $key) !== 1) {
                throw new InvalidArgumentException('ui_smart_reference_frontend_prop_invalid:' . $manifest->componentKey);
            }
            if (is_bool($value)) {
                if ($value) {
                    $attributes[] = $key;
                }
                continue;
            }
            if (!is_scalar($value)) {
                throw new InvalidArgumentException('ui_smart_reference_frontend_value_invalid:' . $manifest->componentKey . ':' . $key);
            }
            if ((string) $value === '') {
                continue;
            }
            $attributes[] = $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return '<' . $tag . ($attributes === [] ? '' : ' ' . implode(' ', $attributes)) . '></' . $tag . '>';
    }

    /** @param array<mixed> $values */
    private function phpArray(array $values, int $level): string
    {
        if ($values === []) {
            return str_repeat('    ', $level) . '[]';
        }
        $indent = str_repeat('    ', $level);
        $childIndent = str_repeat('    ', $level + 1);
        $lines = [$indent . '['];
        foreach ($values as $key => $value) {
            $prefix = array_is_list($values) ? '' : var_export((string) $key, true) . ' => ';
            $lines[] = $childIndent . $prefix . $this->phpValue($value, $level + 1) . ',';
        }
        $lines[] = $indent . ']';

        return implode("\n", $lines);
    }

    private function phpValue(mixed $value, int $level): string
    {
        if (is_array($value)) {
            return ltrim($this->phpArray($value, $level));
        }
        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return var_export($value, true);
        }

        throw new InvalidArgumentException('ui_smart_reference_php_value_invalid');
    }
}
