<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Registry\SmartRegistry;
use Larena\Ui\Runtime\SmartManager;

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

$registry = SmartRegistry::withDefaults();
assert($registry->contributionIds() === ['ui.defaults']);
$manifest = $registry->manifest('ui.input');
assert($manifest->isCanonical());
assert($manifest->ownerPackage === 'larena/ui');
assert(($manifest->provenance['manifest_sha256'] ?? '') !== '');

$artifact = (new SmartManager($registry))->render('ui.input', [
    'id' => 'title',
    'name' => 'title',
    'label' => 'Title',
    'value' => 'Larena',
    'required' => true,
    'type' => 'bordered',
    'size' => '1',
    'error' => false,
    'hint' => '',
], $activation);
assert($artifact->isRenderable());
assert(str_contains($artifact->html(), '<sf-input'));
$payload = $artifact->toArray();
assert(($payload['diagnostics']['component_key'] ?? null) === 'ui.input');
assert(($payload['diagnostics']['manifest_version'] ?? null) === '1.0.0');
assert(($payload['diagnostics']['frontend_tag'] ?? null) === 'sf-input');
assert(($payload['diagnostics']['asset_contract_mode'] ?? null) === 'manifest_verified');
assert(($payload['diagnostics']['production_ready'] ?? null) === false);

$unknownPropRejected = false;
try {
    (new SmartManager($registry))->render('ui.input', [
        'id' => 'title', 'name' => 'title', 'label' => 'Title', 'onclick' => 'alert(1)',
    ], $activation);
} catch (InvalidArgumentException $exception) {
    $unknownPropRejected = $exception->getMessage() === 'ui_smart_prop_unknown:ui.input:onclick';
}
assert($unknownPropRejected);

$collisionRejected = false;
try {
    $registry->registerManifest(SmartComponentManifest::fromJsonFile(
        __DIR__ . '/../../resources/smart/ui-input/manifest.json',
    ));
} catch (InvalidArgumentException $exception) {
    $collisionRejected = $exception->getMessage() === 'ui_smart_manifest_collision:ui.input';
}
assert($collisionRejected);

$unknownRendererRegistry = new SmartRegistry();
$unknownRendererRegistry->registerManifest(SmartComponentManifest::fromArray([
    'schema' => 'larena.ui.smart_manifest.v1',
    'key' => 'vendor.unknown',
    'version' => '1.0.0',
    'owner_package' => 'vendor/package',
    'kind' => 'smart',
    'props' => ['type' => 'object', 'properties' => [], 'additionalProperties' => false],
    'slots' => [], 'events' => [], 'views' => [], 'presets' => [], 'constraints' => [],
    'render' => ['strategy' => 'host', 'renderer' => 'vendor.unknown'],
    'frontend' => ['runtime' => 'simai-framework', 'tag' => 'sf-input'],
    'assets' => [], 'atlas' => [], 'provenance' => [],
]));
$unknownRendererRejected = false;
try {
    (new SmartManager($unknownRendererRegistry))->render('vendor.unknown', [], $activation);
} catch (InvalidArgumentException $exception) {
    $unknownRendererRejected = $exception->getMessage() === 'ui_smart_renderer_unknown:vendor.unknown';
}
assert($unknownRendererRejected);

echo "SmartManagerTest passed.\n";
