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
$reads = 0;
$dataset = static function (string $source) use (&$reads): DataviewDatasetSnapshot {
    ++$reads;
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
assert($reads === 2);
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
    $before = $reads;
    try { $renderer->render($bad); throw new RuntimeException('Invalid list accepted'); }
    catch (InvalidArgumentException) {}
    assert($reads === $before, 'Owner data read before full document preflight');
}
echo "RegisteredListDocumentNodeRendererTest passed: request-local data, two instances, registered columns, pre-read refusal.\n";
