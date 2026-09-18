<?php

declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Larena\Dataview\Contracts\{DataviewDatasetSnapshot,DataviewSourceDescriptor,DataviewQuery,DataviewPagination};
use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Registry\SmartRegistry;
use Larena\Ui\Runtime\{SmartManager,RegisteredListRenderer,RegisteredDocumentRenderer,RegisteredListDocumentNodeRenderer};

$activation = ['activation_owner' => 'larena/core:core.assets', 'physical_publication_ready' => true,
    'writes_database' => false, 'copies_to_root' => false, 'uses_hardcoded_cdn' => false,
    'runtime_pair' => FrontendRuntimeLock::bundled()->pairId(), 'renderable_tags' => ['<script src="/larena/assets/core.js"></script>']];
$expectCount = static function (int $actual, int $expected, string $message = 'Unexpected callback count'): void {
    if ($actual !== $expected) throw new RuntimeException($message);
};
$reads = new class {
    private int $value = 0;
    public function increment(): void { ++$this->value; }
    public function count(): int { return $this->value; }
};
$dataset = static function (string $source) use ($reads): DataviewDatasetSnapshot {
    $reads->increment();
    return new DataviewDatasetSnapshot('sha256:'.str_repeat('a',64), new DataviewSourceDescriptor('auth.users','larena/auth',true),
        new DataviewQuery(), [['record_id' => 1, 'display_name' => 'Literal <admin>', 'private' => 'hidden']], new DataviewPagination(1,20,1), true);
};
$lists = new RegisteredListDocumentNodeRenderer(new RegisteredListRenderer(new SmartManager(SmartRegistry::withDefaults())),
    $dataset, ['auth.users' => ['users' => [['key' => 'display_name', 'label' => 'Name']]]], $activation);
$renderer = new RegisteredDocumentRenderer(static function (array $doc): void {}, [
    'layout.page' => ['validate' => static function (array $node): void {},
        'render' => static fn (array $node, array $slots): string => '<main>'.$slots['default'].'</main>'],
    'larena.registered-list' => $lists->registration(),
]);
$leaf = ['id' => 'list-auth', 'type' => 'larena.registered-list', 'props' => ['source_key' => 'auth.users', 'column_preset' => 'users', 'title' => 'Users']];
$doc = ['schema' => 'simai.composition.document.v1', 'root' => ['id' => 'page', 'type' => 'layout.page', 'slots' => ['default' => [$leaf, [...$leaf, 'id' => 'list-auth-second']]]]];
$html = $renderer->render($doc);
$expectCount($reads->count(), 2);
assert(str_contains($html, 'id="list-auth-table"') && str_contains($html, 'id="list-auth-second-table"'));
assert(str_contains($html, 'Literal &lt;admin&gt;'));
assert(!str_contains($html, 'hidden'));
foreach ([
    ['props' => [...$leaf['props'], 'source_key' => 'unknown.source']],
    ['props' => [...$leaf['props'], 'column_preset' => 'private']],
    ['props' => [...$leaf['props'], 'actor' => 'user:admin_identity:2']],
    ['data' => ['rows' => []]], ['presentation' => ['view' => 'unknown']],
] as $patch) {
    $bad = $doc;
    $bad['root']['slots']['default'][1] = array_replace($bad['root']['slots']['default'][1], $patch);
    $before = $reads->count();
    try { $renderer->render($bad); throw new RuntimeException('Invalid list accepted'); }
    catch (InvalidArgumentException) {}
    $expectCount($reads->count(), $before, 'Owner data read before full document preflight');
}
$queryNode = new RegisteredListDocumentNodeRenderer(new RegisteredListRenderer(new SmartManager(SmartRegistry::withDefaults())),
    $dataset, ['auth.users' => ['users' => [['key' => 'display_name', 'label' => 'Name']]]], $activation,
    ['auth.users' => '/admin/composition-lists/auth.users/query']);
$registration = $queryNode->registration();
($registration['validate'])($leaf);
$hostHtml = ($registration['render'])($leaf, []);
assert(str_contains($hostHtml, 'data-larena-dataview-workbench'));
assert(str_contains($hostHtml, 'data-larena-dataview-state'));
assert(preg_match('~data-larena-dataview-state>(.*?)</script>~s', $hostHtml, $stateMatch) === 1);
$hostState = json_decode($stateMatch[1], true, 64, JSON_THROW_ON_ERROR);
assert($hostState['query_endpoint'] === '/admin/composition-lists/auth.users/query');
assert($hostState['query']['search'] === '');
$alternate = new RegisteredListDocumentNodeRenderer(new RegisteredListRenderer(new SmartManager(SmartRegistry::withDefaults())),
    $dataset, ['auth.users' => ['users' => [['key' => 'display_name', 'label' => 'Name']]]], $activation,
    ['auth.users' => '/product/lists/users/query']);
($alternate->registration()['validate'])($leaf);
foreach (['//external.invalid/query', 'https://external.invalid/query', '/product/../query', '/query?token=secret', '/query#fragment', "/query\n", '/query%2Fexternal', '/query\\external'] as $endpoint) {
    $invalidRoute = new RegisteredListDocumentNodeRenderer(new RegisteredListRenderer(new SmartManager(SmartRegistry::withDefaults())),
        $dataset, ['auth.users' => ['users' => [['key' => 'display_name', 'label' => 'Name']]]], $activation,
        ['auth.users' => $endpoint]);
    $before = $reads->count();
    try { ($invalidRoute->registration()['validate'])($leaf); throw new RuntimeException('Unsafe query route accepted'); }
    catch (InvalidArgumentException) {}
    $expectCount($reads->count(), $before);
}
foreach ([
    [['auth.users' => ['users' => [['key' => 'display_name', 'label' => 'Name'], ['key' => 'display_name', 'label' => 'Duplicate']]]], []],
    [['auth.users' => ['users' => [['key' => 'display_name', 'label' => 123]]]], []],
    [['auth.users' => ['users' => [['key' => 'display_name', 'label' => 'Name']]]], ['auth.users' => ['display_name' => ['type' => 'string', 'operators' => [['handler' => 'bad']]]]]],
] as [$columns, $fields]) {
    $invalid = new RegisteredListDocumentNodeRenderer(new RegisteredListRenderer(new SmartManager(SmartRegistry::withDefaults())),
        $dataset, $columns, $activation, [], $fields);
    $preflight = new RegisteredDocumentRenderer(static function (array $doc): void {}, [
        'layout.page' => ['validate' => static function (array $node): void {}, 'render' => static fn (array $node, array $slots): string => $slots['default']],
        'larena.registered-list' => $invalid->registration(),
    ]);
    $before = $reads->count();
    try { $preflight->render($doc); throw new RuntimeException('Malformed registration accepted'); }
    catch (InvalidArgumentException) {}
    $expectCount($reads->count(), $before, 'Malformed server registration must fail before owner data access');
}
foreach ([['actor' => 'forbidden'], ['user_endpoint' => '//external.invalid/preferences'], ['user_endpoint' => '/product/../preferences']] as $state) {
    $invalidState = new RegisteredListDocumentNodeRenderer(new RegisteredListRenderer(new SmartManager(SmartRegistry::withDefaults())),
        $dataset, ['auth.users' => ['users' => [['key' => 'display_name', 'label' => 'Name']]]], $activation,
        ['auth.users' => '/product/query'], [], static fn (string $source): array => $state);
    $before = $reads->count();
    try { ($invalidState->registration()['render'])($leaf, []); throw new RuntimeException('Unsafe request state accepted'); }
    catch (InvalidArgumentException) {}
    $expectCount($reads->count(), $before, 'Unsafe request-local state must fail before dataset callback');
}
echo "RegisteredListDocumentNodeRendererTest passed: request-local data, two instances, registered columns, pre-read refusal.\n";
