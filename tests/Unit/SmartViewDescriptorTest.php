<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Contracts\SmartViewDescriptor;
use Larena\Ui\Registry\SmartRegistry;

$default = SmartViewDescriptor::fromJsonFile(
    __DIR__ . '/../../resources/smart/ui-dataview/view/default.json',
);
assert($default->isValid());
assert($default->componentKey === 'ui.dataview');
assert($default->viewKey === 'default');
assert(SmartRegistry::withDefaults()->view('ui.dataview', 'default')->toArray() === $default->toArray());

$composite = SmartViewDescriptor::fromJsonFile(
    __DIR__ . '/../../resources/examples/dataview-table.smart-view.json',
);
$resolved = $composite->resolve([], 'compact', ['filterable', 'searchable']);
assert($resolved['props'] === [
    'density' => 'compact',
    'filterable' => true,
    'searchable' => true,
]);
assert(array_keys($resolved['children']) === ['grid', 'pagination', 'toolbar']);

$unknownPresetRejected = false;
try {
    $composite->resolve([], 'unknown');
} catch (InvalidArgumentException $exception) {
    $unknownPresetRejected = str_starts_with($exception->getMessage(), 'ui_smart_view_preset_unknown:');
}
assert($unknownPresetRejected);

$unsafeKeyRejected = false;
try {
    SmartViewDescriptor::fromArray([
        'schema' => SmartViewDescriptor::SCHEMA,
        'component' => 'ui.dataview',
        'view' => 'unsafe',
        'template' => 'default',
        'props' => ['raw_html' => '<script>alert(1)</script>'],
        'presets' => [],
        'modifiers' => [],
        'children' => [],
        'constraints' => [],
    ]);
} catch (InvalidArgumentException $exception) {
    $unsafeKeyRejected = str_starts_with($exception->getMessage(), 'ui_smart_view_value_key_unsafe:');
}
assert($unsafeKeyRejected);

$unsafeValueRejected = false;
try {
    $composite->resolve(['href' => 'javascript:alert(1)']);
} catch (InvalidArgumentException $exception) {
    $unsafeValueRejected = str_starts_with($exception->getMessage(), 'ui_smart_view_value_unsafe:');
}
assert($unsafeValueRejected);

echo "SmartViewDescriptorTest passed.\n";
