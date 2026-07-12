<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Developer\SmartAiCatalogProjection;
use Larena\Ui\Developer\SmartCatalogProjection;
use Larena\Ui\Developer\SmartInvocationExampleBuilder;
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Frontend\SourceBackedComponentRegistry;
use Larena\Ui\Reference\SmartComponentReference;
use Larena\Ui\Registry\SmartRegistry;
use Larena\Ui\Runtime\SmartManager;
use Larena\Ui\Smart;

$components = [
    'ui.button' => ['directory' => 'ui-button', 'tag' => 'sf-button'],
    'ui.input' => ['directory' => 'ui-input', 'tag' => 'sf-input'],
    'ui.textarea' => ['directory' => 'ui-textarea', 'tag' => 'sf-textarea'],
    'ui.checkbox' => ['directory' => 'ui-checkbox', 'tag' => 'sf-checkbox'],
    'ui.dropdown' => ['directory' => 'ui-dropdown', 'tag' => 'sf-dropdown'],
    'ui.dataview' => ['directory' => 'ui-dataview', 'tag' => 'sf-table'],
    'ui.pagination' => ['directory' => 'ui-pagination', 'tag' => 'sf-pagination'],
    'ui.badge' => ['directory' => 'ui-badge', 'tag' => 'sf-badge'],
    'ui.alert' => ['directory' => 'ui-alert', 'tag' => 'sf-alert'],
    'ui.modal' => ['directory' => 'ui-modal', 'tag' => 'sf-modal'],
];
$expectedKeys = array_keys($components);
$expectedReadiness = [
    'safe_to_suggest' => true,
    'safe_to_render' => true,
    'safe_to_bind_data' => false,
    'safe_to_execute_effect' => false,
];
$activation = [
    'schema' => 'larena.core_assets.activation_contract.v1',
    'status' => 'ready',
    'activation_owner' => 'larena/core:core.assets',
    'activation_mode' => 'verified_immutable_bundle',
    'physical_publication_ready' => true,
    'writes_database' => false,
    'copies_to_root' => false,
    'uses_hardcoded_cdn' => false,
    'asset_count' => 1,
    'runtime_pair' => FrontendRuntimeLock::bundled()->pairId(),
    'renderable_tags' => ['<script src="/larena/assets/sf/core.js"></script>'],
];
$assetRows = static fn (array $requirements): array => array_map(
    static fn ($asset): array => [$asset->assetKey, $asset->kind->value, $asset->critical],
    $requirements,
);

$registry = SmartRegistry::withDefaults();
$sourceRegistry = SourceBackedComponentRegistry::bundled();
$manager = new SmartManager($registry);
$catalog = new SmartCatalogProjection($registry, new SmartInvocationExampleBuilder());
$ai = new SmartAiCatalogProjection($catalog);
$alternateRenderCount = 0;
$legacyRendererId = implode('.', ['ui', 'sf_element']);

assert(SmartComponentManifest::isStableKey('ui'));
assert(SmartComponentManifest::isStableKey('ui.sf.element'));
assert(SmartComponentManifest::isComponentKey('ui.button'));
assert(SmartComponentManifest::isComponentKey('docara.page_title_field'));
assert(SmartComponentManifest::isComponentKey('ui.button2'));
assert(SmartComponentManifest::isComponentKey('admin.shell.workspace'));
assert(!SmartComponentManifest::isComponentKey('ui'));
assert(!SmartComponentManifest::isComponentKey('UI.button'));
assert(SmartComponentManifest::isRendererId(SmartComponentManifest::SIMAI_FRAMEWORK_RENDERER_ID));
assert(!SmartComponentManifest::isRendererId($legacyRendererId));
assert(!SmartComponentManifest::isRendererId('ui.element'));
assert(!SmartComponentManifest::isRendererId('ui.adapter2.element'));
assert(!SmartComponentManifest::isRendererId('ui.sf.element.v2'));

$buttonFixture = json_decode(
    (string) file_get_contents(dirname(__DIR__, 2) . '/resources/smart/ui-button/manifest.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);
assert(is_array($buttonFixture));
$manifestRejected = static function (array $fixture): bool {
    try {
        SmartComponentManifest::fromArray($fixture);
    } catch (InvalidArgumentException) {
        return true;
    }

    return false;
};
foreach (['ui', 'UI.button', 'ui.-button'] as $invalidComponentKey) {
    $fixture = $buttonFixture;
    $fixture['key'] = $invalidComponentKey;
    assert($manifestRejected($fixture));
}
foreach ([$legacyRendererId, 'ui.element', 'ui.adapter2.element', 'ui.sf.element.v2', 'ui.custom.element'] as $invalidRendererId) {
    $fixture = $buttonFixture;
    $fixture['render']['renderer'] = $invalidRendererId;
    assert($manifestRejected($fixture));
}

assert(array_map(
    static fn ($entry): string => $entry->key,
    $catalog->components('en'),
) === $expectedKeys);
assert(count($registry->manifests()) === count($components));

foreach ($components as $key => $definition) {
    $path = dirname(__DIR__, 2) . '/resources/smart/' . $definition['directory'] . '/manifest.json';
    $parsed = SmartComponentManifest::fromJsonFile($path);
    $manifest = $registry->manifest($key);
    assert($parsed->toArray() === $manifest->toArray());
    assert($manifest->componentKey === $key);
    assert($manifest->rendererId === SmartComponentManifest::SIMAI_FRAMEWORK_RENDERER_ID);
    assert($manifest->frontendRuntime === 'simai-framework');
    assert($manifest->frontendTag === $definition['tag']);
    assert(($manifest->provenance['runtime_lock'] ?? null) === 'resources/sf/runtime-lock.json');
    assert(($manifest->provenance['upstream_revision'] ?? null) === 'dd786bbae98391fb21df9b4e1e6cd402ead0614c');
    assert(($manifest->provenance['reference_status'] ?? null) === 'source_backed');
    foreach ($manifest->eventSchema as $event) {
        assert(is_array($event));
        assert(($event['backend_handler_binding'] ?? null) === false);
    }

    $runtimeDefinition = $sourceRegistry->get($definition['tag']);
    $allowedProps = array_map('strval', $runtimeDefinition['attributes'] ?? []);
    if ($definition['tag'] === 'sf-table') {
        $allowedProps[] = 'data';
    }
    if ($definition['tag'] === 'sf-dropdown') {
        $allowedProps[] = 'options';
    }
    $manifestProps = array_keys($manifest->propsSchema['properties'] ?? []);
    assert(array_values(array_diff($manifestProps, $allowedProps)) === []);

    $runtimeAssets = $assetRows(Smart::assetGraph($definition['tag'])->requirements);
    $manifestAssets = $assetRows($manifest->assetRequirements);
    assert($manifestAssets === $runtimeAssets);

    $reference = new SmartComponentReference($manifest);
    $resolved = $reference->resolve();
    $sourceRegistry->assertPropsAllowed($definition['tag'], $resolved['props']);
    $artifact = $manager->render($key, $resolved['props'], $activation);
    assert($artifact->isRenderable());
    assert(str_contains($artifact->html(), '<' . $definition['tag']));
    assert(($artifact->toArray()['diagnostics']['asset_contract_mode'] ?? null) === 'manifest_verified');

    foreach ($reference->controls() as $control) {
        $controlKey = (string) $control['key'];
        $current = $resolved['controls'][$controlKey];
        $alternative = match ($control['widget']) {
            'boolean' => !$current,
            'select' => ($control['options'][0] ?? null) !== $current
                ? ($control['options'][0] ?? null)
                : ($control['options'][1] ?? null),
            'text' => 'Changed ' . str_replace('-', ' ', $controlKey),
            default => null,
        };
        assert(is_string($alternative) || is_bool($alternative));
        assert($alternative !== $current);
        $changed = $reference->resolve([$controlKey => $alternative], true);
        assert($changed['controls'][$controlKey] === $alternative);
        assert($changed['props'] !== $resolved['props']);
        $changedArtifact = $manager->render($key, $changed['props'], $activation);
        assert($changedArtifact->isRenderable());
        assert($changedArtifact->html() !== $artifact->html());
        $alternateRenderCount++;
    }

    foreach (['en', 'ru'] as $locale) {
        $entry = $catalog->component($key, $locale);
        assert($entry->title !== '');
        assert($entry->description !== '');
        assert($entry->readiness === $expectedReadiness);
        assert(array_map(
            static fn (array $control): string => (string) $control['key'],
            $entry->controls,
        ) === array_map(
            static fn (array $control): string => (string) $control['key'],
            $reference->controls(),
        ));
        foreach ($entry->controls as $control) {
            assert(is_string($control['label'] ?? null) && trim((string) $control['label']) !== '');
        }
    }
}
assert($alternateRenderCount === 48);

$expectedSizes = ['1/3', '1/2', '1', '2', '3'];
assert(($registry->manifest('ui.input')->propsSchema['properties']['size']['enum'] ?? null) === $expectedSizes);
assert(($registry->manifest('ui.textarea')->propsSchema['properties']['size']['enum'] ?? null) === $expectedSizes);
assert(!isset($registry->manifest('ui.pagination')->propsSchema['properties']['main']));
assert(!isset($registry->manifest('ui.pagination')->propsSchema['properties']['middle']));

$dropdownEvent = $registry->manifest('ui.dropdown')->eventSchema['sf-dropdown:change'] ?? null;
assert(is_array($dropdownEvent));
assert(($dropdownEvent['kind'] ?? null) === 'custom');
$modalEvents = [
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
foreach ($modalEvents as $eventName) {
    $event = $registry->manifest('ui.modal')->eventSchema[$eventName] ?? null;
    assert(is_array($event));
    assert(($event['kind'] ?? null) === 'custom');
}

$inputReference = new SmartComponentReference($registry->manifest('ui.input'));
$inputControlKeys = array_map(
    static fn (array $control): string => (string) $control['key'],
    $inputReference->controls(),
);
assert($inputControlKeys === ['label', 'value', 'size', 'type', 'required', 'disabled', 'error', 'hint']);

$dataView = $catalog->component('ui.dataview');
assert($dataView->examples['backend']['available'] === true);
assert($dataView->examples['frontend']['available'] === false);
assert(($dataView->examples['frontend']['reason'] ?? null) === 'ui_smart_reference_frontend_value_invalid:ui.dataview:data');
assert(!array_key_exists('value', $dataView->examples['frontend']));
assert(str_contains(
    $manager->render('ui.dataview', $dataView->exampleProps, $activation)->html(),
    'data-larena-smart-hydration',
));

$dropdown = $catalog->component('ui.dropdown');
assert($dropdown->examples['backend']['available'] === true);
assert($dropdown->examples['frontend']['available'] === false);
assert(($dropdown->examples['frontend']['reason'] ?? null) === 'ui_smart_reference_frontend_value_invalid:ui.dropdown:options');
assert(!array_key_exists('value', $dropdown->examples['frontend']));

$firstAi = $ai->toArray('en');
$secondAi = $ai->toArray('en');
$firstJson = $ai->toJson('en');
assert($firstAi === $secondAi);
assert($firstJson === $ai->toJson('en'));
assert(array_column($firstAi['components'], 'key') === $expectedKeys);
assert($firstAi['recipes'] === []);
assert(!str_contains($firstJson, '/Users/'));
assert(!str_contains($firstJson, 'file://'));
assert(!str_contains($firstJson, 'generated_at'));
assert(!str_contains($firstJson, '"callback":'));
assert(!str_contains($firstJson, '"callbacks":'));

echo "SmartComponentLibraryTest passed.\n";
