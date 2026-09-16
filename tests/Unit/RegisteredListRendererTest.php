<?php

declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
$loader = new Composer\Autoload\ClassLoader();
$loader->addPsr4('Larena\\Ui\\', dirname(__DIR__, 2).'/src');
$loader->register(true);

use Larena\Dataview\Contracts\DataviewDatasetSnapshot;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewQuery;
use Larena\Dataview\Contracts\DataviewPagination;
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Registry\SmartRegistry;
use Larena\Ui\Runtime\SmartManager;
use Larena\Ui\Runtime\RegisteredListRenderer;

$activation = ['schema' => 'larena.core_assets.activation_contract.v1', 'status' => 'ready',
    'activation_owner' => 'larena/core:core.assets', 'activation_mode' => 'verified_immutable_bundle',
    'physical_publication_ready' => true, 'writes_database' => false, 'copies_to_root' => false,
    'uses_hardcoded_cdn' => false, 'asset_count' => 1, 'runtime_pair' => FrontendRuntimeLock::bundled()->pairId(),
    'renderable_tags' => ['<script src="/larena/assets/sf/core.js"></script>']];
$manager = new SmartManager(SmartRegistry::withDefaults());
$renderer = new RegisteredListRenderer($manager);
$page = new DataviewDatasetSnapshot('sha256:'.str_repeat('a',64), new DataviewSourceDescriptor('auth.users','larena/auth',true),
    new DataviewQuery(), [['record_id' => 'record_Привет_1', 'title' => '<script>unsafe</script>', 'private' => 'not projected']], new DataviewPagination(1,20,1), true);
$columns = [['key' => 'title', 'label' => 'Название']];
$a = $renderer->render($page, 'list-alpha', 'Records', $columns, $activation);
$b = $renderer->render($page, 'list-beta', 'Records', $columns, $activation);
assert($a->isRenderable()); assert(str_contains($a->html(), 'id="list-alpha-table"'));
assert(str_contains($b->html(), 'id="list-beta-table"'));
assert(str_contains($a->html(), 'id="list-alpha-pagination"'));
assert(!str_contains($a->html(), 'not projected'));
assert(!str_contains($a->html(), '<script>unsafe</script>'));
assert(str_contains($a->html(), 'record_Привет_1') || str_contains($a->html(), 'record_\\u041f'));
foreach ([['bad id', $columns], ['valid', [['key' => 'missing', 'label' => 'Missing']]], ['valid', [...$columns, ...$columns]]] as [$id,$projection]) {
    try { $renderer->render($page, $id, 'Records', $projection, $activation); throw new RuntimeException('Invalid presentation accepted.'); }
    catch (InvalidArgumentException) {}
}
foreach ([['grid' => ['id' => 'not-data']], ['unknown' => ['data' => []]]] as $binding) {
    try { $manager->renderView('dataview.table', 'default', ['title' => 'Records'], $activation, requestDataBindings: $binding); throw new RuntimeException('Invalid request binding accepted.'); }
    catch (InvalidArgumentException) {}
}
try {
    $manager->renderView('dataview.table', 'default', ['title' => 'Records'], $activation,
        childProps: ['grid' => ['data' => ['rows' => [['title' => '<script>unsafe</script>']]]]]);
    throw new RuntimeException('Unsafe structural data accepted.');
} catch (InvalidArgumentException) {}
echo "RegisteredListRendererTest passed: registered composite, separate instance IDs, projection and safe hydration.\n";
