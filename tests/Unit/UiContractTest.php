<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\ComponentAtlasEntry;
use Larena\Ui\Contracts\DesignPackDescriptor;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\SmartViewDescriptor;
use Larena\Ui\Contracts\UiAssetGraph;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Contracts\UiPlaygroundScenario;
use Larena\Ui\Contracts\UiResourcePackManifest;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Enums\UiAssetKind;

$asset = new UiAssetRequirement('component.button.css', UiAssetKind::Css, true);
assert($asset->isValid());

$manifest = new SmartComponentManifest('ui.button', ['label' => ['type' => 'string']], ['default'], RenderStrategy::Native, [$asset]);
assert($manifest->isValid());

$view = new SmartViewDescriptor('ui.button.primary', 'ui.button');
assert($view->isValid());

$render = new BackendRenderResult('<button>Save</button>', RenderStrategy::Native, HydrationContract::none(), [$asset]);
assert($render->isSafe());

$graph = new UiAssetGraph([$asset], ['component:ui.button']);
assert($graph->isValid());
assert(count($graph->criticalRequirements()) === 1);

$designPack = new DesignPackDescriptor('design.default', ['color.primary' => '#000000']);
assert($designPack->isPortableDesignOnly());

$atlas = new ComponentAtlasEntry($manifest, true, false);
assert($atlas->isTrustworthy());

$scenario = new UiPlaygroundScenario('ui.button.default', $manifest);
assert($scenario->isValid());

$resourcePack = new UiResourcePackManifest('ui.default', ['ui.button'], ['dist/ui.css']);
assert($resourcePack->isValid());

$adminReferenceReadiness = $resourcePack->adminFrontendSmokeReadiness(
    UiResourcePackManifest::adminFrontendReferenceCustomElements(),
);
assert($adminReferenceReadiness['status'] === 'blocked_missing_required_smart_components');
assert($adminReferenceReadiness['missing_custom_elements'] === [
    'admin.menu' => 'sf-admin-menu',
    'admin.menu_item' => 'sf-admin-menu-item',
    'navigation.breadcrumbs' => 'sf-breadcrumbs',
    'data.table' => 'sf-table',
    'data.tree_item' => 'sf-tree-item',
]);
assert($adminReferenceReadiness['boundaries']['activation_owned_by_core_assets']);
assert($adminReferenceReadiness['boundaries']['no_frontend_runtime_copy']);
assert($adminReferenceReadiness['carrier_source_status']['navigation.breadcrumbs']['source_backed_status'] === 'component_backed_adapter_candidate');
assert($adminReferenceReadiness['carrier_source_status']['data.table']['source_backed_status'] === 'smart_runtime_missing_static_utility_available');
assert($adminReferenceReadiness['carrier_source_status']['admin.menu']['source_backed_status'] === 'artifact_only_source_blocker');

$adminCarrierBlockers = UiResourcePackManifest::adminFrontendCarrierBlockers();
assert($adminCarrierBlockers === []);
assert(UiResourcePackManifest::adminFrontendCarrierSourceBlockers() === [
    'admin.menu',
    'admin.menu_item',
    'navigation.breadcrumbs',
    'data.table',
    'data.tree_item',
]);

$adminCarrierAssetGraph = UiResourcePackManifest::adminFrontendCarrierAssetGraph();
assert($adminCarrierAssetGraph->isValid());
assert(count($adminCarrierAssetGraph->criticalRequirements()) === 5);
foreach ($adminCarrierAssetGraph->criticalRequirements() as $requirement) {
    assert($requirement->finalPathOwnedByCoreAssets);
}

$packageOwnedCarriers = UiResourcePackManifest::adminFrontendPackageOwnedCarriers();
assert(array_keys($packageOwnedCarriers) === [
    'admin.menu',
    'admin.menu_item',
    'navigation.breadcrumbs',
    'data.table',
    'data.tree_item',
]);
assert(UiResourcePackManifest::adminFrontendPackageOwnedCustomElements() === [
    'sf-admin-menu',
    'sf-admin-menu-item',
    'sf-breadcrumbs',
    'sf-table',
    'sf-tree-item',
]);
foreach ($packageOwnedCarriers as $carrier) {
    assert($carrier['source_backed_status'] === 'larena_owned_fallback_carrier');
    assert(is_file(__DIR__ . '/../../' . $carrier['resource_path']));
}

$adminCompleteReadiness = $resourcePack->adminFrontendSmokeReadiness(array_values(
    UiResourcePackManifest::ADMIN_FRONTEND_REQUIRED_CUSTOM_ELEMENTS,
));
assert($adminCompleteReadiness['status'] === 'ready_for_browser_smoke');
assert($adminCompleteReadiness['missing_custom_elements'] === []);

$adminPackageOwnedReadiness = $resourcePack->adminFrontendSmokeReadiness([
    ...UiResourcePackManifest::adminFrontendReferenceCustomElements(),
    ...UiResourcePackManifest::adminFrontendPackageOwnedCustomElements(),
]);
assert($adminPackageOwnedReadiness['status'] === 'ready_for_browser_smoke');
assert($adminPackageOwnedReadiness['missing_custom_elements'] === []);
