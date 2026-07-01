<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\FrontendRenderArtifact;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\UiAssetGraph;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Contracts\UiResourcePackManifest;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Enums\UiAssetKind;
use Larena\Ui\Runtime\InMemoryUiRuntime;

$runtime = new InMemoryUiRuntime([
    'data.table.read_only_adapter',
    'admin.shell.read_only_route.css',
]);

$manifest = new SmartComponentManifest(
    'sf.table',
    [
        'caption' => ['type' => 'string'],
        'columns' => ['type' => 'list'],
        'rows' => ['type' => 'list'],
    ],
    [],
    RenderStrategy::Native,
    [
        new UiAssetRequirement('data.table.read_only_adapter', UiAssetKind::Module, true),
        new UiAssetRequirement('admin.shell.read_only_route.css', UiAssetKind::Css, true),
    ],
);

$render = $runtime->renderBackend($manifest, [
    'caption' => 'Frontend artifact',
    'columns' => ['Name', 'Status'],
    'rows' => [['render artifact', 'ready']],
]);
$assetGraph = $runtime->collectAssetGraph($manifest);
$activation = [
    'schema' => 'larena.core_assets.activation_contract.v1',
    'status' => 'activation_contract_ready_package_asset_descriptor_pilot',
    'activation_owner' => 'larena/core:core.assets',
    'activation_mode' => 'package_asset_descriptor_read_only_route',
    'physical_publication_ready' => true,
    'writes_database' => false,
    'copies_to_root' => false,
    'uses_hardcoded_cdn' => false,
    'asset_count' => 2,
    'renderable_tags' => [
        '<script type="module" src="/larena/internal/package-owned-admin-frontend/assets/data.table.read_only_adapter"></script>',
        '<link rel="stylesheet" href="/larena/internal/package-owned-admin-frontend/assets/admin.shell.read_only_route.css">',
    ],
];

$artifact = new FrontendRenderArtifact($render, $assetGraph, $activation, [
    'source' => 'test',
    'root_frontend_source_of_truth' => false,
]);

assert($artifact->isRenderable());
assert($artifact->html() === $render->html);
assert(count($artifact->assetTags()) === 2);
assert(str_contains($artifact->assetTags()[0], 'type="module"'));

$serialized = $artifact->toArray();
assert($serialized['schema'] === 'larena.ui.frontend_render_artifact.v1');
assert($serialized['owner_package'] === 'larena/ui');
assert($serialized['renderable'] === true);
assert($serialized['html_is_render_artifact'] === true);
assert($serialized['html'] === $render->html);
assert($serialized['hydration']['valid'] === true);
assert($serialized['hydration']['strategy'] === $render->hydration->strategy->value);
assert($serialized['backend_render']['safe'] === true);
assert($serialized['backend_render']['copied_frontend_source'] === false);
assert($serialized['asset_graph']['valid'] === true);
assert($serialized['asset_graph']['requirement_count'] === 2);
assert($serialized['asset_activation']['activation_owner'] === 'larena/core:core.assets');
assert($serialized['asset_activation']['renderable_tag_count'] === 2);
assert($serialized['asset_activation']['renderable_tags'] === $artifact->assetTags());
assert($serialized['diagnostics']['root_frontend_source_of_truth'] === false);

$unsafeRender = new BackendRenderResult(
    '<sf-table></sf-table>',
    RenderStrategy::Native,
    HydrationContract::none(),
    $manifest->assetRequirements,
    true,
);
assert(!(new FrontendRenderArtifact($unsafeRender, $assetGraph, $activation))->isRenderable());

$badOwnerActivation = $activation;
$badOwnerActivation['activation_owner'] = 'root-app';
assert(!(new FrontendRenderArtifact($render, $assetGraph, $badOwnerActivation))->isRenderable());

$rootCopyActivation = $activation;
$rootCopyActivation['copies_to_root'] = true;
assert(!(new FrontendRenderArtifact($render, $assetGraph, $rootCopyActivation))->isRenderable());

$missingTagsActivation = $activation;
$missingTagsActivation['renderable_tags'] = [];
assert(!(new FrontendRenderArtifact($render, $assetGraph, $missingTagsActivation))->isRenderable());

$emptyGraph = new UiAssetGraph([], []);
assert(!(new FrontendRenderArtifact($render, $emptyGraph, $activation))->isRenderable());

$descriptor = UiResourcePackManifest::adminReadOnlyShellAssetDescriptor();
assert($descriptor['activation_owner'] === 'larena/core:core.assets');

echo "FrontendRenderArtifactTest passed.\n";
