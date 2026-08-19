<?php

declare(strict_types=1);

use Larena\Ui\Runtime\SmartManager;
use Larena\Ui\Frontend\FrontendRuntimeLock;

require __DIR__.'/../../vendor/autoload.php';

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
    'renderable_tags' => ['<link rel="stylesheet" href="/larena/assets/sf/core.css">'],
];

$artifact = SmartManager::withDefaults()->renderView(
    'admin.record_editor',
    'default',
    ['title' => 'Edit record', 'mode' => 'update'],
    $activation,
    ['fields' => '<sf-input></sf-input>', 'actions' => '<sf-button></sf-button>'],
);

assert($artifact->isRenderable());
assert(str_starts_with($artifact->html(), '<details class="larena-admin-record-editor"'));
assert(str_contains($artifact->html(), '<summary class="larena-admin-record-editor__title">Edit record</summary>'));
assert(!str_contains($artifact->html(), 'data-larena-composite="admin.record_editor" open'));
assert(str_contains($artifact->html(), 'data-larena-composite="admin.record_editor"'));
assert(str_contains($artifact->html(), 'data-larena-slot="fields"'));
assert(str_contains($artifact->html(), '<sf-input></sf-input>'));
assert(str_contains($artifact->html(), 'data-larena-slot="actions"'));
assert(($artifact->diagnostics['component_key'] ?? null) === 'admin.record_editor');

$expanded = SmartManager::withDefaults()->renderView(
    'admin.record_editor',
    'default',
    ['title' => 'Edit record', 'mode' => 'update', 'expanded' => true],
    $activation,
    ['fields' => '', 'actions' => ''],
);
assert(str_contains($expanded->html(), 'data-larena-composite="admin.record_editor" open'));

echo "AdminRecordEditorSmartViewTest passed.\n";
