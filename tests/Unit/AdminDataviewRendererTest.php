<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Larena\Dataview\Contracts\DataviewActionPolicy;
use Larena\Dataview\Contracts\DataviewFieldDescriptor;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewSourceProvider;
use Larena\Dataview\Contracts\DataviewViewDescriptor;
use Larena\Dataview\Enums\DataviewViewType;
use Larena\Dataview\Runtime\DataviewTableRuntime;
use Larena\Ui\Runtime\AdminDataviewRenderer;
use Larena\Ui\Frontend\FrontendRuntimeLock;

$source = new DataviewSourceDescriptor('auth.users', 'larena/auth', true);
$provider = new class($source) implements DataviewSourceProvider {
    public function __construct(private DataviewSourceDescriptor $source) {}
    public function descriptor(): DataviewSourceDescriptor { return $this->source; }
    public function rows(): array { return [['name' => ['text' => '<Admin>', 'href' => '/admin/users/1'], 'status' => ['type' => 'badge', 'tone' => 'enabled', 'text' => 'Enabled']]]; }
};
$view = new DataviewViewDescriptor('auth.users.table', $source, DataviewViewType::Table, [
    new DataviewFieldDescriptor('name', 'link', 'lang:auth.name'),
    new DataviewFieldDescriptor('status', 'badge', 'lang:auth.status'),
]);
$page = (new DataviewTableRuntime())->project($provider, $view, DataviewActionPolicy::readOnly());
$activation = [
    'schema' => 'larena.core_assets.activation_contract.v1', 'status' => 'ready', 'activation_owner' => 'larena/core:core.assets',
    'activation_mode' => 'read_only_route', 'physical_publication_ready' => true, 'writes_database' => false,
    'copies_to_root' => false, 'uses_hardcoded_cdn' => false, 'asset_count' => 1,
    'runtime_pair' => FrontendRuntimeLock::bundled()->pairId(), 'renderable_tags' => ['<link rel="stylesheet" href="/ui.css">'],
];
$artifact = (new AdminDataviewRenderer())->render($page, ['name' => 'Name', 'status' => 'Status'], [], 'Users', '/admin/users', $activation);
assert($artifact->isRenderable());
assert(str_contains($artifact->html(), 'data-larena-smart-component="admin.dataview"'));
assert(str_contains($artifact->html(), '<sf-table'));
assert(str_contains($artifact->html(), 'data-larena-read-only="true"'));
assert(!str_contains($artifact->html(), ' read-only='));
assert(str_contains($artifact->html(), 'selectable="false"'));
assert(str_contains($artifact->html(), 'settings="false"'));
assert(str_contains($artifact->html(), 'actions="false"'));
assert(str_contains($artifact->html(), '\\u003CAdmin\\u003E'));
assert(!str_contains($artifact->html(), '<Admin>'));
assert(str_contains($artifact->html(), 'type="application/json"'));
assert(($artifact->toArray()['diagnostics']['smart_manager']['component_key'] ?? null) === 'ui.dataview');
assert(($artifact->toArray()['diagnostics']['row_count'] ?? null) === 1);
assert(in_array('smart-component:ui.dataview', $artifact->assetGraph->explain, true));

$frameworkPlan = [
    'schema' => 'larena.ui.framework_resolved_plan.v1',
    'adapter' => [
        'id' => 'docara.pages.admin.collection',
        'upstream_recipe' => 'recipe.admin.collection',
        'support' => ['upstream_gap' => 'smart.table.read-only', 'fallback' => 'larena.ui.sf-runtime-bridge'],
    ],
    'compatibility_id' => FrontendRuntimeLock::bundled()->pairId(),
    'profile' => 'plain-assets-v1',
    'registry_sha256' => str_repeat('a', 64),
    'plan_sha256' => str_repeat('b', 64),
    'entry_ids' => [
        'component.buttons',
        'recipe.admin.collection',
        'smart.table',
        'utility.display',
        'utility.flex-direction',
        'utility.gap',
        'utility.overflow',
        'utility.width',
    ],
    'kinds' => ['component', 'recipe', 'smart-component', 'utility'],
    'entries' => [
        ['id' => 'smart.table', 'kind' => 'smart-component', 'runtime' => ['tags' => ['sf-table']]],
    ],
    'effects_allowed' => false,
];
$sourceBackedArtifact = (new AdminDataviewRenderer())->render(
    $page,
    ['name' => 'Name', 'status' => 'Status'],
    [],
    'Users',
    '/admin/users',
    $activation,
    $frameworkPlan,
);
assert(str_contains($sourceBackedArtifact->html(), 'class="larena-panel larena-dataview flex flex-col gap-1"'));
assert(str_contains($sourceBackedArtifact->html(), 'class="larena-dataview-content overflow-x-auto w-full" data-framework-region="content"'));
assert(!str_contains($sourceBackedArtifact->html(), 'larena-panel larena-dataview flex flex-col gap-1 overflow-x-auto'));
assert(str_contains($sourceBackedArtifact->html(), 'data-framework-recipe="admin.collection"'));
assert(str_contains($sourceBackedArtifact->html(), 'data-larena-read-only="true"'));
assert(($sourceBackedArtifact->toArray()['diagnostics']['framework_contract']['uses_framework_utilities_for_layout'] ?? null) === true);
assert(($sourceBackedArtifact->toArray()['diagnostics']['framework_contract']['layout_utility_classes'] ?? null) === [
    'utility.display' => ['flex'],
    'utility.flex-direction' => ['flex-col'],
    'utility.gap' => ['gap-1'],
    'utility.overflow' => ['overflow-x-auto'],
    'utility.width' => ['w-full'],
]);
assert(($sourceBackedArtifact->toArray()['diagnostics']['framework_contract']['layout_utility_regions'] ?? null) === [
    'collection' => [
        'utility.display' => ['flex'],
        'utility.flex-direction' => ['flex-col'],
        'utility.gap' => ['gap-1'],
    ],
    'content' => [
        'utility.overflow' => ['overflow-x-auto'],
        'utility.width' => ['w-full'],
    ],
]);

$missingGapPlan = $frameworkPlan;
$missingGapPlan['entry_ids'] = array_values(array_filter(
    $missingGapPlan['entry_ids'],
    static fn (string $id): bool => $id !== 'utility.gap',
));
try {
    (new AdminDataviewRenderer())->render($page, ['name' => 'Name', 'status' => 'Status'], [], 'Users', '/admin/users', $activation, $missingGapPlan);
    throw new RuntimeException('Expected missing layout utility to fail closed.');
} catch (InvalidArgumentException $exception) {
    assert($exception->getMessage() === 'ui_admin_dataview_framework_utility_missing:utility.gap');
}
echo "AdminDataviewRendererTest: OK\n";
