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
    'renderable_tags' => ['<link rel="stylesheet" href="/ui.css">'],
];
$artifact = (new AdminDataviewRenderer())->render($page, ['name' => 'Name', 'status' => 'Status'], [], 'Users', '/admin/users', $activation);
assert($artifact->isRenderable());
assert(str_contains($artifact->html(), 'data-larena-smart-component="admin.dataview"'));
assert(str_contains($artifact->html(), '<sf-table'));
assert(str_contains($artifact->html(), '\\u003CAdmin\\u003E'));
assert(!str_contains($artifact->html(), '<Admin>'));
assert(str_contains($artifact->html(), 'type="application/json"'));
echo "AdminDataviewRendererTest: OK\n";
