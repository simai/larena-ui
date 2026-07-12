<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Developer\SmartCatalogProjection;
use Larena\Ui\Developer\SmartInvocationExampleBuilder;
use Larena\Ui\Registry\SmartRegistry;

$registry = SmartRegistry::withDefaults();
$projection = new SmartCatalogProjection($registry, new SmartInvocationExampleBuilder());
$expectedKeys = [
    'ui.button',
    'ui.input',
    'ui.textarea',
    'ui.checkbox',
    'ui.dropdown',
    'ui.dataview',
    'ui.pagination',
    'ui.badge',
    'ui.alert',
    'ui.modal',
];

$english = $projection->components('en');
assert(array_map(static fn ($entry): string => $entry->key, $english) === $expectedKeys);
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

$hiddenData = $registry->manifest('ui.button')->toArray();
$hiddenData['key'] = 'ui.hidden';
$hiddenData['atlas']['visible'] = false;
$hiddenData['provenance']['manifest_path'] = 'resources/smart/ui-hidden/manifest.json';
$hiddenData['provenance']['manifest_sha256'] = str_repeat('e', 64);
$registry->registerManifest(SmartComponentManifest::fromArray($hiddenData));
$hiddenRejected = false;
try {
    $projection->component('ui.hidden');
} catch (InvalidArgumentException $exception) {
    $hiddenRejected = $exception->getMessage() === 'ui_smart_catalog_component_not_visible:ui.hidden';
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
assert(array_map(static fn ($entry): string => $entry->key, $sorted) === ['ui.alpha', ...$expectedKeys]);
assert($sorted[0]->controls[0]['label'] === 'Text');
assert($sorted[0]->controls[1]['option_labels']['primary'] === 'Primary');
assert(!array_key_exists('callback', $sorted[0]->provenance));
assert(!array_key_exists('evidence', $sorted[0]->provenance));

$realEventNames = [
    'sf-dropdown:change',
    'modal:ready',
    'modal:update',
    'modal:before-open',
    'modal:after-open',
    'modal:before-close',
    'modal:after-close',
    'modal:before-hide',
    'modal:after-hide',
    'modal:before-show',
    'modal:after-show',
];
$eventData = $registry->manifest('ui.button')->toArray();
$eventData['key'] = 'ui.real_events';
$eventData['events'] = array_fill_keys($realEventNames, [
    'kind' => 'dom',
    'backend_handler_binding' => false,
]);
$eventData['provenance']['manifest_path'] = 'resources/smart/ui-real-events/manifest.json';
$eventData['provenance']['manifest_sha256'] = str_repeat('f', 64);
$registry->registerManifest(SmartComponentManifest::fromArray($eventData));
$eventEntry = $projection->component('ui.real_events');
assert(array_keys($eventEntry->manifestProjection['events']) === $realEventNames);

foreach (['bad event', 'bad/event', 'modal::ready', 'onclick', 'onload:ready'] as $index => $unsafeEvent) {
    $unsafeEventData = $eventData;
    $unsafeEventData['key'] = 'ui.unsafe_event_' . $index;
    $unsafeEventData['events'] = [$unsafeEvent => ['kind' => 'dom', 'backend_handler_binding' => false]];
    $unsafeEventData['provenance']['manifest_path'] = 'resources/smart/ui-unsafe-event-' . $index . '/manifest.json';
    $unsafeEventData['provenance']['manifest_sha256'] = str_repeat((string) ($index + 1), 64);
    $registry->registerManifest(SmartComponentManifest::fromArray($unsafeEventData));
    $unsafeEventRejected = false;
    try {
        $projection->component($unsafeEventData['key']);
    } catch (InvalidArgumentException $exception) {
        $unsafeEventRejected = $exception->getMessage() === 'ui_smart_catalog_manifest_invalid:' . $unsafeEventData['key'] . ':events';
    }
    assert($unsafeEventRejected);
}

$complex = $projection->component('ui.dataview');
assert($complex->examples['backend']['available'] === true);
assert($complex->examples['frontend']['available'] === false);
assert($complex->examples['frontend']['reason'] === 'ui_smart_reference_frontend_value_invalid:ui.dataview:data');

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
