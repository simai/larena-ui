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
assert($a->isRenderable());
assert(str_contains($a->html(), '<noscript><table'));
assert(str_contains($a->html(), '&lt;script&gt;unsafe&lt;/script&gt;')); assert(str_contains($a->html(), 'id="list-alpha-table"'));
assert(str_contains($b->html(), 'id="list-beta-table"'));
assert(str_contains($a->html(), 'id="list-alpha-pagination"'));
$domA = new DOMDocument(); $domB = new DOMDocument();
@$domA->loadHTML($a->html()); @$domB->loadHTML($b->html());
$toolbarIds = static function (DOMDocument $dom): array {
    $result = [];
    foreach ((new DOMXPath($dom))->query('//*[contains(concat(" ", normalize-space(@class), " "), " larena-dataview-toolbar ")]//*[@id]') as $node) {
        if (!$node instanceof DOMElement) throw new RuntimeException('Expected toolbar element');
        $id = $node->getAttribute('id');
        assert(strlen($id) <= 80);
        assert(!in_array($id, $result, true), 'Toolbar controls require unique IDs within a list');
        $result[] = $id;
    }
    return $result;
};
$idsA = $toolbarIds($domA); $idsB = $toolbarIds($domB);
assert(count($idsA) >= 9, 'Test must inspect every registered toolbar control');
assert(array_intersect($idsA, $idsB) === [], 'Sibling toolbars must not reuse control IDs');
$document = new DOMDocument();
@$document->loadHTML($a->html());
$paginationNode = (new DOMXPath($document))->query('//sf-pagination[@id="list-alpha-pagination"]')->item(0);
assert($paginationNode instanceof DOMElement);
assert($paginationNode->getAttribute('page-sizes') === '10,20,50,100');
assert($paginationNode->getAttribute('page-size') === '20', 'Pagination must use the same owner page size as table rows');

assert(!str_contains($a->html(), 'not projected'));
assert(!str_contains($a->html(), '<script>unsafe</script>'));
assert(str_contains($a->html(), 'record_Привет_1') || str_contains($a->html(), 'record_\\u041f'));
$withFilters = $renderer->render($page, 'list-filtered', 'Records', $columns, $activation, [
    'title' => ['type' => 'string', 'operators' => ['eq', 'contains', 'in'], 'sortable' => true],
    'private' => ['type' => 'string', 'operators' => ['eq'], 'sortable' => true],
]);
assert(preg_match('/<script[^>]*data-larena-smart-hydration="list-filtered-table"[^>]*>(.*?)<\/script>/s', $withFilters->html(), $hydration) === 1);
$descriptor = json_decode($hydration[1], true, 64, JSON_THROW_ON_ERROR);
assert($descriptor['props']['data']['filterFields'] === [[
    'key' => 'title', 'label' => 'Название', 'filter' => ['enabled' => true, 'control' => 'text',
        'operators' => ['eq', 'contains'], 'defaultOperator' => 'eq', 'options' => []],
]]);
try {
    (new ReflectionMethod($renderer, 'render'))->invoke($renderer, $page, 'list-invalid', 'Records', $columns, $activation,
        json_decode('{"title":{"type":"string","operators":[{"handler":"arbitrary"}]}}', true, 64, JSON_THROW_ON_ERROR));
    throw new RuntimeException('Malformed query contract accepted.');
} catch (InvalidArgumentException) {}
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
$longInstance = 'list-'.str_repeat('a', 75);
$controls = $renderer->recordControls($longInstance, [['key' => str_repeat('b', 40), 'label' => '<unsafe>']], $activation);
$otherControls = $renderer->recordControls('second-owner', [['key' => 'caption', 'label' => 'Caption']], $activation);
$controlsDom = new DOMDocument();
@$controlsDom->loadHTML($controls);
$controlIds = [];
foreach ((new DOMXPath($controlsDom))->query('//*[@id]') as $controlNode) {
    if (!$controlNode instanceof DOMElement) throw new RuntimeException('Expected control element');
    $controlId = $controlNode->getAttribute('id');
    assert(strlen($controlId) <= 80, 'Controls must fit Framework IDs even at maximum instance and field lengths');
    assert(!isset($controlIds[$controlId]), 'Each control requires a unique ID');
    assert(!str_contains($otherControls, 'id="'.$controlId.'"'), 'Owner controls must not share IDs');
    $controlIds[$controlId] = true;
}
assert(str_contains($controls, 'data-larena-record-control="record-save"'));
assert(!str_contains($controls, '<unsafe>'));
foreach (['bad field', str_repeat('x', 41), 'x" onclick="bad'] as $invalidField) {
    try {
        $renderer->recordControls('safe', [['key' => $invalidField, 'label' => 'Field']], $activation);
        throw new RuntimeException('Unsafe record control field accepted.');
    } catch (InvalidArgumentException) {}
}
$preferences = $renderer->preferenceControls($longInstance, $activation);
assert(str_contains($preferences, 'data-larena-dataview-reset'));
assert(str_contains($preferences, 'Сбросить личные настройки'));
assert(!str_contains($renderer->preferenceControls('other-owner', $activation), 'id="preferences-'.substr(hash('sha256', $longInstance), 0, 20).'-reset"'));
assert(!str_contains($preferences, 'endpoint'));
echo "RegisteredListRendererTest passed: registered composite, isolated bounded controls, projection and safe hydration.\n";
