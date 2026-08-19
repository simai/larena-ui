<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

use InvalidArgumentException;
use JsonException;

final readonly class SmartViewDescriptor
{
    public const SCHEMA = 'larena.ui.smart_view.v1';

    /**
     * @param array<string, mixed> $constraints
     * @param array<string, mixed> $props
     * @param array<string, array{props: array<string, mixed>}> $presets
     * @param array<string, array{props: array<string, mixed>}> $modifiers
     * @param array<string, mixed> $children
     */
    public function __construct(
        public string $viewKey,
        public string $componentKey,
        public array $constraints = [],
        public string $template = 'default',
        public array $props = [],
        public array $presets = [],
        public array $modifiers = [],
        public array $children = [],
    ) {
    }

    public static function fromJsonFile(string $path): self
    {
        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            throw new InvalidArgumentException('ui_smart_view_file_missing');
        }
        try {
            $decoded = json_decode((string) file_get_contents($realPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('ui_smart_view_json_invalid');
        }
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new InvalidArgumentException('ui_smart_view_json_invalid');
        }

        return self::fromArray($decoded);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        self::exactKeys($data, ['children', 'component', 'constraints', 'modifiers', 'presets', 'props', 'schema', 'template', 'view']);
        if (($data['schema'] ?? null) !== self::SCHEMA) {
            throw new InvalidArgumentException('ui_smart_view_schema_unknown');
        }
        foreach (['props', 'presets', 'modifiers', 'children', 'constraints'] as $key) {
            if (!is_array($data[$key] ?? null) || ($data[$key] !== [] && array_is_list($data[$key]))) {
                throw new InvalidArgumentException('ui_smart_view_' . $key . '_invalid');
            }
        }
        $presets = self::variantMap($data['presets'], 'preset');
        $modifiers = self::variantMap($data['modifiers'], 'modifier');
        $children = self::children($data['children'], 0);
        self::assertSafeValue($data['props'], 'props', 0);
        self::assertSafeValue($data['constraints'], 'constraints', 0);

        $view = new self(
            (string) ($data['view'] ?? ''),
            (string) ($data['component'] ?? ''),
            $data['constraints'],
            (string) ($data['template'] ?? ''),
            $data['props'],
            $presets,
            $modifiers,
            $children,
        );
        if (!$view->isValid()) {
            throw new InvalidArgumentException('ui_smart_view_invalid:' . $view->componentKey . ':' . $view->viewKey);
        }

        return $view;
    }

    public function isValid(): bool
    {
        return self::isVariantKey($this->viewKey)
            && SmartComponentManifest::isComponentKey($this->componentKey)
            && self::isVariantKey($this->template);
    }

    /**
     * @param array<string, mixed> $inputProps
     * @param list<string> $modifierKeys
     * @return array{component:string,view:string,template:string,preset:?string,modifiers:list<string>,props:array<string,mixed>,children:array<string,mixed>,constraints:array<string,mixed>}
     */
    public function resolve(array $inputProps = [], ?string $preset = null, array $modifierKeys = []): array
    {
        self::assertSafeValue($inputProps, 'input_props', 0);
        $resolved = $this->props;
        if ($preset !== null) {
            if (!isset($this->presets[$preset])) {
                throw new InvalidArgumentException('ui_smart_view_preset_unknown:' . $this->componentKey . ':' . $this->viewKey . ':' . $preset);
            }
            $resolved = array_replace_recursive($resolved, $this->presets[$preset]['props']);
        }
        $normalizedModifiers = [];
        foreach ($modifierKeys as $modifier) {
            if (!self::isVariantKeyValue($modifier) || !isset($this->modifiers[$modifier])) {
                throw new InvalidArgumentException('ui_smart_view_modifier_unknown:' . $this->componentKey . ':' . $this->viewKey . ':' . (string) $modifier);
            }
            if (in_array($modifier, $normalizedModifiers, true)) {
                throw new InvalidArgumentException('ui_smart_view_modifier_duplicate:' . $modifier);
            }
            $normalizedModifiers[] = $modifier;
            $resolved = array_replace_recursive($resolved, $this->modifiers[$modifier]['props']);
        }
        $resolved = array_replace_recursive($resolved, $inputProps);
        self::assertSafeValue($resolved, 'resolved_props', 0);

        return [
            'component' => $this->componentKey,
            'view' => $this->viewKey,
            'template' => $this->template,
            'preset' => $preset,
            'modifiers' => $normalizedModifiers,
            'props' => $resolved,
            'children' => $this->children,
            'constraints' => $this->constraints,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => self::SCHEMA,
            'component' => $this->componentKey,
            'view' => $this->viewKey,
            'template' => $this->template,
            'props' => $this->props,
            'presets' => $this->presets,
            'modifiers' => $this->modifiers,
            'children' => $this->children,
            'constraints' => $this->constraints,
        ];
    }

    public static function isVariantKey(string $key): bool
    {
        return preg_match('/^[a-z][a-z0-9_.-]{0,79}$/', $key) === 1;
    }

    private static function isVariantKeyValue(mixed $key): bool
    {
        return is_string($key) && self::isVariantKey($key);
    }

    /** @param array<string, mixed> $input @return array<string, array{props: array<string, mixed>}> */
    private static function variantMap(array $input, string $kind): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            if (!self::isVariantKeyValue($key) || !is_array($value) || array_is_list($value)) {
                throw new InvalidArgumentException('ui_smart_view_' . $kind . '_invalid:' . (string) $key);
            }
            self::exactKeys($value, ['props']);
            if (!is_array($value['props'] ?? null) || ($value['props'] !== [] && array_is_list($value['props']))) {
                throw new InvalidArgumentException('ui_smart_view_' . $kind . '_props_invalid:' . $key);
            }
            self::assertSafeValue($value['props'], $kind . '.' . $key . '.props', 0);
            $result[$key] = ['props' => $value['props']];
        }
        ksort($result, SORT_STRING);

        return $result;
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private static function children(array $input, int $depth): array
    {
        if ($depth > 6 || count($input) > 50) {
            throw new InvalidArgumentException('ui_smart_view_children_limit_exceeded');
        }
        $children = [];
        foreach ($input as $key => $child) {
            if (!self::isVariantKeyValue($key) || !is_array($child) || array_is_list($child)) {
                throw new InvalidArgumentException('ui_smart_view_child_invalid:' . (string) $key);
            }
            self::exactKeys($child, ['children', 'component', 'modifiers', 'preset', 'props', 'slot', 'view']);
            $component = $child['component'] ?? null;
            $view = $child['view'] ?? null;
            $slot = $child['slot'] ?? null;
            $preset = $child['preset'] ?? null;
            if (!is_string($component) || !SmartComponentManifest::isComponentKey($component)
                || !is_string($view) || !self::isVariantKey($view)
                || !is_string($slot) || !self::isVariantKey($slot)
                || ($preset !== null && (!is_string($preset) || !self::isVariantKey($preset)))) {
                throw new InvalidArgumentException('ui_smart_view_child_contract_invalid:' . $key);
            }
            if (!is_array($child['modifiers'] ?? null) || !array_is_list($child['modifiers']) || count($child['modifiers']) > 20) {
                throw new InvalidArgumentException('ui_smart_view_child_modifiers_invalid:' . $key);
            }
            $modifiers = [];
            foreach ($child['modifiers'] as $modifier) {
                if (!is_string($modifier) || !self::isVariantKey($modifier) || in_array($modifier, $modifiers, true)) {
                    throw new InvalidArgumentException('ui_smart_view_child_modifier_invalid:' . $key);
                }
                $modifiers[] = $modifier;
            }
            if (!is_array($child['props'] ?? null) || ($child['props'] !== [] && array_is_list($child['props']))) {
                throw new InvalidArgumentException('ui_smart_view_child_props_invalid:' . $key);
            }
            self::assertSafeValue($child['props'], 'child.' . $key . '.props', 0);
            if (!is_array($child['children'] ?? null) || ($child['children'] !== [] && array_is_list($child['children']))) {
                throw new InvalidArgumentException('ui_smart_view_child_children_invalid:' . $key);
            }
            $children[$key] = [
                'component' => $component,
                'view' => $view,
                'preset' => $preset,
                'modifiers' => $modifiers,
                'slot' => $slot,
                'props' => $child['props'],
                'children' => self::children($child['children'], $depth + 1),
            ];
        }
        ksort($children, SORT_STRING);

        return $children;
    }

    /** @param array<string, mixed> $value @param list<string> $expected */
    private static function exactKeys(array $value, array $expected): void
    {
        $keys = array_keys($value);
        sort($keys, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($keys !== $expected) {
            throw new InvalidArgumentException('ui_smart_view_unknown_key');
        }
    }

    private static function assertSafeValue(mixed $value, string $path, int $depth): void
    {
        if ($depth > 8) {
            throw new InvalidArgumentException('ui_smart_view_value_depth_exceeded:' . $path);
        }
        if (is_array($value)) {
            if (count($value) > 100) {
                throw new InvalidArgumentException('ui_smart_view_value_collection_too_large:' . $path);
            }
            foreach ($value as $key => $child) {
                if (!is_int($key)) {
                    $name = strtolower((string) $key);
                    if (preg_match('/^[a-z][a-z0-9_.-]{0,119}$/', $name) !== 1
                        || preg_match('/(^|[_.-])(html|css|javascript|js|php|script|callback|callable|class|method|password|token|secret|credential|absolute_path|template_path)([_.-]|$)/', $name) === 1) {
                        throw new InvalidArgumentException('ui_smart_view_value_key_unsafe:' . $path . '.' . $name);
                    }
                }
                self::assertSafeValue($child, $path . '.' . (string) $key, $depth + 1);
            }
            return;
        }
        if (!is_scalar($value) && $value !== null) {
            throw new InvalidArgumentException('ui_smart_view_value_type_invalid:' . $path);
        }
        if (is_string($value)
            && (strlen($value) > 16_384
                || preg_match('/<\?php|<\/?[a-z!][^>]*>|javascript\s*:|data\s*:\s*text\/html/i', $value) === 1
                || str_contains(str_replace('\\', '/', $value), '../')
                || preg_match('#^(?:file://|/Users/|/home/|/private/|[A-Za-z]:/)#', str_replace('\\', '/', $value)) === 1)) {
            throw new InvalidArgumentException('ui_smart_view_value_unsafe:' . $path);
        }
    }
}
