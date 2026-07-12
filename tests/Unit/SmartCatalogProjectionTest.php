<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Developer\SmartCatalogProjection;
use Larena\Ui\Developer\SmartInvocationExampleBuilder;
use Larena\Ui\Registry\SmartRegistry;

$registry = SmartRegistry::withDefaults();
$projection = new SmartCatalogProjection($registry, new SmartInvocationExampleBuilder());

$english = $projection->components('en');
assert(count($english) === 1);
assert($english[0]->key === 'ui.button');
assert($english[0]->title === 'Button');
assert($english[0]->manifest === $registry->manifest('ui.button'));
assert($english[0]->readiness === [
    'safe_to_suggest' => true,
    'safe_to_render' => true,
    'safe_to_bind_data' => false,
    'safe_to_execute_effect' => false,
]);
assert($english[0]->examples['backend']['available'] === true);
assert($english[0]->examples['frontend']['available'] === true);
assert(str_contains((string) $english[0]->examples['backend']['value'], "'ui.button'"));
assert(str_contains((string) $english[0]->examples['frontend']['value'], '<sf-button'));
assert($english[0]->provenance['manifest_path'] === 'resources/smart/ui-button/manifest.json');
assert(preg_match('/^[a-f0-9]{64}$/', $english[0]->provenance['manifest_sha256']) === 1);
assert(!str_starts_with($english[0]->provenance['manifest_path'], '/'));
assert(($english[0]->manifestProjection['schema'] ?? null) === 'larena.ui.smart_manifest_projection.v1');
assert(!array_key_exists('atlas', $english[0]->manifestProjection));
assert(($english[0]->toArray()['manifest']['key'] ?? null) === 'ui.button');

$russian = $projection->component('ui.button', 'ru');
assert($russian->title === 'Кнопка');
assert(str_contains($russian->description, 'Simai Framework'));
assert($russian->controls[0]['label'] === 'Текст');
assert($russian->controls[1]['option_labels']['primary'] === 'Основная');

$hiddenRejected = false;
try {
    $projection->component('ui.input');
} catch (InvalidArgumentException $exception) {
    $hiddenRejected = $exception->getMessage() === 'ui_smart_catalog_component_not_visible:ui.input';
}
assert($hiddenRejected);

$unknownRejected = false;
try {
    $projection->component('ui.unknown');
} catch (InvalidArgumentException $exception) {
    $unknownRejected = $exception->getMessage() === 'ui_smart_manifest_unknown:ui.unknown';
}
assert($unknownRejected);

$alphaData = $registry->manifest('ui.button')->toArray();
$alphaData['key'] = 'ui.alpha';
$alphaData['atlas']['order'] = 5;
$alphaData['atlas']['title'] = 'Alpha';
$alphaData['atlas']['i18n']['en']['title'] = 'Alpha';
$alphaData['atlas']['i18n']['ru']['title'] = 'Альфа';
$alphaData['atlas']['controls'][0]['label'] = 'Injected label';
$alphaData['atlas']['controls'][1]['option_labels'] = ['primary' => 'Injected option'];
$alphaData['provenance'] = [
    'source' => 'larena/ui',
    'runtime_lock' => 'resources/sf/runtime-lock.json',
    'reference_status' => 'source_backed',
    'manifest_path' => 'resources/smart/ui-alpha/manifest.json',
    'manifest_sha256' => str_repeat('a', 64),
    'callback' => 'DangerousCallback::run',
    'evidence' => '/Users/example/private.json',
];
$registry->registerManifest(SmartComponentManifest::fromArray($alphaData));
$sorted = $projection->components();
assert(array_map(static fn ($entry): string => $entry->key, $sorted) === ['ui.alpha', 'ui.button']);
assert($sorted[0]->controls[0]['label'] === 'Text');
assert($sorted[0]->controls[1]['option_labels']['primary'] === 'Primary');
assert(!array_key_exists('callback', $sorted[0]->provenance));
assert(!array_key_exists('evidence', $sorted[0]->provenance));

$complexData = $registry->manifest('ui.dataview')->toArray();
$complexData['key'] = 'ui.complex_table';
$complexData['atlas'] = [
    'visible' => true,
    'title' => 'Complex table',
    'description' => 'A source-backed table with a structured data payload.',
    'category' => 'collections',
    'order' => 20,
    'status' => 'contract_only',
    'readiness' => [
        'safe_to_suggest' => false,
        'safe_to_render' => false,
        'safe_to_bind_data' => false,
        'safe_to_execute_effect' => false,
    ],
    'states' => ['default', 'loading', 'empty', 'error'],
    'accessibility' => ['labelled_region', 'keyboard_scroll'],
    'product_href' => '/admin/ui/components',
    'example_props' => [
        'aria-label' => 'Pages',
        'data' => ['columns' => [], 'rows' => []],
    ],
    'controls' => [
        ['key' => 'aria-label', 'source' => 'prop', 'widget' => 'text', 'min_length' => 1, 'max_length' => 120],
    ],
    'i18n' => [
        'en' => [
            'title' => 'Complex table',
            'description' => 'A source-backed table with a structured data payload.',
            'controls' => ['aria-label' => 'Accessible name'],
            'options' => [],
        ],
        'ru' => [
            'title' => 'Таблица со структурированными данными',
            'description' => 'Таблица с проверяемым структурированным payload данных.',
            'controls' => ['aria-label' => 'Доступное название'],
            'options' => [],
        ],
    ],
];
$complexData['provenance']['manifest_path'] = 'resources/smart/ui-complex-table/manifest.json';
$complexData['provenance']['manifest_sha256'] = str_repeat('b', 64);
$registry->registerManifest(SmartComponentManifest::fromArray($complexData));
$complex = $projection->component('ui.complex_table');
assert($complex->examples['backend']['available'] === true);
assert($complex->examples['frontend']['available'] === false);
assert(str_contains((string) $complex->examples['frontend']['reason'], 'frontend_value_invalid'));

$invalidData = $alphaData;
$invalidData['key'] = 'ui.invalid';
$invalidData['atlas']['order'] = 99;
unset($invalidData['atlas']['readiness']);
$invalidData['provenance']['manifest_path'] = 'resources/smart/ui-invalid/manifest.json';
$invalidData['provenance']['manifest_sha256'] = str_repeat('c', 64);
$registry->registerManifest(SmartComponentManifest::fromArray($invalidData));
$invalidVisibleRejected = false;
try {
    $projection->component('ui.invalid');
} catch (InvalidArgumentException $exception) {
    $invalidVisibleRejected = $exception->getMessage() === 'ui_smart_catalog_manifest_invalid:ui.invalid:readiness';
}
assert($invalidVisibleRejected);

$missingRendererData = $alphaData;
$missingRendererData['key'] = 'ui.missing_renderer';
$missingRendererData['render']['renderer'] = 'ui.missing_renderer';
$missingRendererData['provenance']['manifest_path'] = 'resources/smart/ui-missing-renderer/manifest.json';
$missingRendererData['provenance']['manifest_sha256'] = str_repeat('d', 64);
$registry->registerManifest(SmartComponentManifest::fromArray($missingRendererData));
$missingRendererRejected = false;
try {
    $projection->component('ui.missing_renderer');
} catch (InvalidArgumentException $exception) {
    $missingRendererRejected = $exception->getMessage() === 'ui_smart_renderer_unknown:ui.missing_renderer';
}
assert($missingRendererRejected);

echo "SmartCatalogProjectionTest passed.\n";
