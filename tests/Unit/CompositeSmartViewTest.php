<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

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
    'runtime_pair' => FrontendRuntimeLock::bundled()->pairId(),
    'renderable_tags' => ['<script src="/larena/assets/sf/core.js"></script>'],
];

$manager = new SmartManager(SmartRegistry::withDefaults());
$artifact = $manager->renderView('admin.collection', 'default', ['title' => 'Team members'], $activation);
assert($artifact->isRenderable());
assert(str_contains($artifact->html(), 'data-larena-composite="admin.collection"'));
assert(str_contains($artifact->html(), 'data-larena-composite="dataview.table"'));
assert(str_contains($artifact->html(), 'data-larena-composite="dataview.toolbar"'));
assert(str_contains($artifact->html(), '<sf-input'));
assert(str_contains($artifact->html(), '<sf-table'));
assert(str_contains($artifact->html(), '<sf-pagination'));
assert(($artifact->diagnostics['composite_child_count'] ?? null) === 1);
assert(count($artifact->assetGraph->requirements) > 0);

$table = $manager->renderView('dataview.table', 'default', ['title' => 'Pages'], $activation, [], null, [], [
    'grid' => ['data' => ['columns' => [['key' => 'title', 'label' => 'Title']], 'rows' => [['title' => 'Welcome']]]],
    'pagination' => ['current' => 2, 'total' => 4],
]);
assert(str_contains($table->html(), 'Welcome'));
assert(str_contains($table->html(), 'current="2"'));

$unknownChildRejected = false;
try {
    $manager->renderView('dataview.table', 'default', ['title' => 'Pages'], $activation, [], null, [], ['unknown' => []]);
} catch (InvalidArgumentException $exception) {
    $unknownChildRejected = str_starts_with($exception->getMessage(), 'ui_smart_view_child_props_invalid:');
}
assert($unknownChildRejected);

echo "CompositeSmartViewTest passed.\n";
