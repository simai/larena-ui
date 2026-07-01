<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

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
assert($adminReferenceReadiness['carrier_source_status']['form.checkbox']['source_backed_status'] === 'larena_owned_fallback_carrier');
assert($adminReferenceReadiness['carrier_source_status']['toolbar.icon_button']['browser_smoke_ready']);

$adminCarrierBlockers = UiResourcePackManifest::adminFrontendCarrierBlockers();
assert($adminCarrierBlockers === []);
assert(UiResourcePackManifest::adminFrontendCarrierSourceBlockers() === []);

$adminCarrierAssetGraph = UiResourcePackManifest::adminFrontendCarrierAssetGraph();
assert($adminCarrierAssetGraph->isValid());
assert(count($adminCarrierAssetGraph->criticalRequirements()) === 12);
foreach ($adminCarrierAssetGraph->criticalRequirements() as $requirement) {
    assert($requirement->finalPathOwnedByCoreAssets);
    assert($requirement->kind === UiAssetKind::Module);
}

$packageOwnedCarriers = UiResourcePackManifest::adminFrontendPackageOwnedCarriers();
assert(array_keys($packageOwnedCarriers) === [
    'admin.menu',
    'admin.menu_item',
    'navigation.breadcrumbs',
    'data.table',
    'data.tree_item',
    'form.checkbox',
    'media.avatar',
    'navigation.pagination',
    'status.progress_scale',
    'status.tag',
    'theme.toggle',
    'toolbar.icon_button',
]);
assert(UiResourcePackManifest::adminFrontendPackageOwnedCustomElements() === [
    'sf-admin-menu',
    'sf-admin-menu-item',
    'sf-breadcrumbs',
    'sf-table',
    'sf-tree-item',
    'sf-checkbox',
    'sf-avatar',
    'sf-pagination',
    'sf-progress-scale',
    'sf-tag',
    'sf-toggle',
    'sf-icon-button',
]);
foreach ($packageOwnedCarriers as $carrier) {
    assert($carrier['source_backed_status'] === 'larena_owned_fallback_carrier');
    assert(is_file(__DIR__ . '/../../' . $carrier['resource_path']));
}

$publicationAssets = UiResourcePackManifest::adminFrontendPackageOwnedCarrierPublicationAssets();
assert(count($publicationAssets) === 12);
assert($publicationAssets[0]['asset_key'] === 'admin.menu.smart');
assert($publicationAssets[0]['kind'] === 'module');
foreach ($publicationAssets as $asset) {
    assert($asset['source_backed_status'] === 'larena_owned_fallback_carrier');
    assert(is_file(__DIR__ . '/../../' . $asset['resource_path']));
}

$routePublicationAssets = UiResourcePackManifest::adminFrontendReadOnlyRoutePublicationAssets();
assert(count($routePublicationAssets) === 17);
assert($routePublicationAssets[12]['asset_key'] === 'admin.shell.read_only_route.css');
assert($routePublicationAssets[12]['kind'] === 'css');
assert(is_file(__DIR__ . '/../../' . $routePublicationAssets[12]['resource_path']));
assert($routePublicationAssets[13]['asset_key'] === 'admin.foundation.preview.css');
assert($routePublicationAssets[13]['kind'] === 'css');
assert(is_file(__DIR__ . '/../../' . $routePublicationAssets[13]['resource_path']));
assert($routePublicationAssets[14]['asset_key'] === 'root.preview.internal_artifact.css');
assert($routePublicationAssets[15]['asset_key'] === 'root.preview.file_operation_guarded_flow.css');
assert($routePublicationAssets[16]['asset_key'] === 'root.preview.guarded_data_content_runtime_bridge.css');

$routeAssetGraph = UiResourcePackManifest::adminFrontendReadOnlyRouteAssetGraph();
assert($routeAssetGraph->isValid());
assert(count($routeAssetGraph->criticalRequirements()) === 17);

$assetDescriptorPath = __DIR__ . '/../../assets.yaml';
$assetDescriptor = json_decode((string) file_get_contents($assetDescriptorPath), true, 512, JSON_THROW_ON_ERROR);
assert($assetDescriptor === UiResourcePackManifest::adminReadOnlyShellAssetDescriptor());
assert($assetDescriptor['schema'] === 'larena.core_assets.set.v1');
assert($assetDescriptor['owner_package'] === 'larena/ui');
assert($assetDescriptor['activation_owner'] === 'larena/core:core.assets');
assert($assetDescriptor['policy']['local_only'] === true);
assert($assetDescriptor['policy']['allow_cdn'] === false);
assert($assetDescriptor['policy']['allow_template_direct_include'] === false);
assert($assetDescriptor['policy']['final_path_owned_by_core_assets'] === true);
assert(count($assetDescriptor['resources']) === 6);
assert($assetDescriptor['resources'][2]['key'] === 'admin.foundation.preview.css');
assert($assetDescriptor['resources'][3]['key'] === 'root.preview.internal_artifact.css');
assert($assetDescriptor['resources'][4]['key'] === 'root.preview.file_operation_guarded_flow.css');
assert($assetDescriptor['resources'][5]['key'] === 'root.preview.guarded_data_content_runtime_bridge.css');
foreach ($assetDescriptor['resources'] as $resource) {
    assert(is_file(__DIR__ . '/../../' . $resource['path']));
    assert(str_starts_with($resource['path'], 'resources/assets/'));
}

$artifactPublicationPlan = UiResourcePackManifest::adminFrontendArtifactAdapterPublicationPlan();
assert($artifactPublicationPlan['schema'] === 'larena.ui.admin_frontend_artifact_adapter_publication_plan.v1');
assert($artifactPublicationPlan['status'] === 'adapter_plan_ready_with_reference_warnings');
assert($artifactPublicationPlan['owners']['smart_manifest'] === 'larena/ui');
assert($artifactPublicationPlan['owners']['asset_activation'] === 'larena/core:core.assets');
assert($artifactPublicationPlan['owners']['shell_route'] === 'larena/admin');
assert(in_array('larena/ui package-owned smart carrier resources', $artifactPublicationPlan['allowed_sources'], true));
assert(in_array('ui-admin/dist copied into root simai/larena', $artifactPublicationPlan['forbidden_sources'], true));
assert(in_array('ui-admin/node_modules copied into root simai/larena', $artifactPublicationPlan['forbidden_sources'], true));
assert(in_array('hardcoded cdn.jsdelivr.net runtime dependency in Larena templates', $artifactPublicationPlan['forbidden_sources'], true));
assert(in_array('legacy SF5 label as Larena runtime or contract name', $artifactPublicationPlan['forbidden_sources'], true));
assert($artifactPublicationPlan['reference_warnings']['cdn_reference_requires_core_assets_repackaging'] === true);
assert($artifactPublicationPlan['reference_warnings']['legacy_sf5_label_requires_larena_naming_adapter'] === true);
assert($artifactPublicationPlan['reference_warnings']['write_events_require_guarded_settings_or_crud_launch'] === true);
assert($artifactPublicationPlan['boundaries']['reference_only'] === true);
assert($artifactPublicationPlan['boundaries']['root_frontend_source_of_truth'] === false);
assert($artifactPublicationPlan['boundaries']['frontend_distribution_copy_allowed'] === false);
assert($artifactPublicationPlan['boundaries']['node_modules_copy_allowed'] === false);
assert($artifactPublicationPlan['boundaries']['hardcoded_cdn_allowed_in_larena_runtime'] === false);
assert($artifactPublicationPlan['boundaries']['legacy_sf5_contract_name_allowed'] === false);
assert($artifactPublicationPlan['boundaries']['production_ui_claim'] === false);

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
