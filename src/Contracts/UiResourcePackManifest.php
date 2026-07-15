<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

use Larena\Ui\Enums\UiAssetKind;

final readonly class UiResourcePackManifest
{
    /**
     * Custom elements required by the current admin frontend artifact before a
     * real browser smoke can be claimed as Larena-backed.
     *
     * @var array<string, string>
     */
    public const ADMIN_FRONTEND_REQUIRED_CUSTOM_ELEMENTS = [
        'admin.menu' => 'sf-admin-menu',
        'admin.menu_item' => 'sf-admin-menu-item',
        'navigation.breadcrumbs' => 'sf-breadcrumbs',
        'data.table' => 'sf-table',
        'data.tree_item' => 'sf-tree-item',
        'form.checkbox' => 'sf-checkbox',
        'media.avatar' => 'sf-avatar',
        'navigation.pagination' => 'sf-pagination',
        'status.progress_scale' => 'sf-progress-scale',
        'status.tag' => 'sf-tag',
        'theme.toggle' => 'sf-toggle',
        'toolbar.icon_button' => 'sf-icon-button',
    ];

    /**
     * Source-backed reference carriers seen in the local Simai Framework smart
     * component mirrors. This is readiness evidence only; larena/ui still does
     * not copy, publish or activate these assets.
     *
     * @var array<string, string>
     */
    public const ADMIN_FRONTEND_REFERENCE_CARRIERS = [
        'checkbox' => 'sf-checkbox',
        'avatar' => 'sf-avatar',
        'pagination' => 'sf-pagination',
        'progress-scale' => 'sf-progress-scale',
        'tags' => 'sf-tag',
        'toggle' => 'sf-toggle',
        'icon-buttons' => 'sf-icon-button',
    ];

    /**
     * Source classification for admin carriers required before browser smoke.
     * These values describe evidence and next work; they do not activate assets.
     *
     * @var array<string, array{
     *     custom_element: string,
     *     source_backed_status: string,
     *     source_kind: string,
     *     adapter_required: bool,
     *     browser_smoke_ready: bool
     * }>
     */
    public const ADMIN_FRONTEND_CARRIER_SOURCE_STATUS = [
        'admin.menu' => [
            'custom_element' => 'sf-admin-menu',
            'source_backed_status' => 'artifact_only_source_blocker',
            'source_kind' => 'ui_admin_artifact_only',
            'adapter_required' => true,
            'browser_smoke_ready' => false,
        ],
        'admin.menu_item' => [
            'custom_element' => 'sf-admin-menu-item',
            'source_backed_status' => 'artifact_only_source_blocker',
            'source_kind' => 'ui_admin_artifact_only',
            'adapter_required' => true,
            'browser_smoke_ready' => false,
        ],
        'navigation.breadcrumbs' => [
            'custom_element' => 'sf-breadcrumbs',
            'source_backed_status' => 'component_backed_adapter_candidate',
            'source_kind' => 'simai_framework_component',
            'adapter_required' => true,
            'browser_smoke_ready' => false,
        ],
        'data.table' => [
            'custom_element' => 'sf-table',
            'source_backed_status' => 'smart_runtime_missing_static_utility_available',
            'source_kind' => 'simai_framework_static_utility',
            'adapter_required' => true,
            'browser_smoke_ready' => false,
        ],
        'data.tree_item' => [
            'custom_element' => 'sf-tree-item',
            'source_backed_status' => 'artifact_only_source_blocker',
            'source_kind' => 'ui_admin_artifact_only',
            'adapter_required' => true,
            'browser_smoke_ready' => false,
        ],
        'form.checkbox' => [
            'custom_element' => 'sf-checkbox',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'source_kind' => 'simai_framework_reference_adapter',
            'adapter_required' => true,
            'browser_smoke_ready' => true,
        ],
        'media.avatar' => [
            'custom_element' => 'sf-avatar',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'source_kind' => 'simai_framework_reference_adapter',
            'adapter_required' => true,
            'browser_smoke_ready' => true,
        ],
        'navigation.pagination' => [
            'custom_element' => 'sf-pagination',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'source_kind' => 'simai_framework_reference_adapter',
            'adapter_required' => true,
            'browser_smoke_ready' => true,
        ],
        'status.progress_scale' => [
            'custom_element' => 'sf-progress-scale',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'source_kind' => 'simai_framework_reference_adapter',
            'adapter_required' => true,
            'browser_smoke_ready' => true,
        ],
        'status.tag' => [
            'custom_element' => 'sf-tag',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'source_kind' => 'simai_framework_reference_adapter',
            'adapter_required' => true,
            'browser_smoke_ready' => true,
        ],
        'theme.toggle' => [
            'custom_element' => 'sf-toggle',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'source_kind' => 'simai_framework_reference_adapter',
            'adapter_required' => true,
            'browser_smoke_ready' => true,
        ],
        'toolbar.icon_button' => [
            'custom_element' => 'sf-icon-button',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'source_kind' => 'simai_framework_reference_adapter',
            'adapter_required' => true,
            'browser_smoke_ready' => true,
        ],
    ];

    /**
     * @var list<string>
     */
    public const ADMIN_FRONTEND_SOURCE_BLOCKER_KEYS = [
    ];

    /**
     * Active blockers after Larena-owned fallback resources are considered.
     *
     * @var list<string>
     */
    public const ADMIN_FRONTEND_BLOCKED_CARRIER_KEYS = [];

    /**
     * @var array<string, array{asset_key: string, kind: UiAssetKind}>
     */
    public const ADMIN_FRONTEND_CARRIER_ASSET_REQUIREMENTS = [
        'admin.menu' => [
            'asset_key' => 'admin.menu.smart',
            'kind' => UiAssetKind::Module,
        ],
        'admin.menu_item' => [
            'asset_key' => 'admin.menu_item.smart',
            'kind' => UiAssetKind::Module,
        ],
        'navigation.breadcrumbs' => [
            'asset_key' => 'navigation.breadcrumbs.component',
            'kind' => UiAssetKind::Module,
        ],
        'data.table' => [
            'asset_key' => 'data.table.read_only_adapter',
            'kind' => UiAssetKind::Module,
        ],
        'data.tree_item' => [
            'asset_key' => 'data.tree_item.smart',
            'kind' => UiAssetKind::Module,
        ],
        'form.checkbox' => [
            'asset_key' => 'form.checkbox.smart',
            'kind' => UiAssetKind::Module,
        ],
        'media.avatar' => [
            'asset_key' => 'media.avatar.smart',
            'kind' => UiAssetKind::Module,
        ],
        'navigation.pagination' => [
            'asset_key' => 'navigation.pagination.smart',
            'kind' => UiAssetKind::Module,
        ],
        'status.progress_scale' => [
            'asset_key' => 'status.progress_scale.smart',
            'kind' => UiAssetKind::Module,
        ],
        'status.tag' => [
            'asset_key' => 'status.tag.smart',
            'kind' => UiAssetKind::Module,
        ],
        'theme.toggle' => [
            'asset_key' => 'theme.toggle.smart',
            'kind' => UiAssetKind::Module,
        ],
        'toolbar.icon_button' => [
            'asset_key' => 'toolbar.icon_button.smart',
            'kind' => UiAssetKind::Module,
        ],
    ];

    /**
     * Package-owned stylesheet for the read-only admin route smoke shell. This
     * keeps Blade markup free of inline CSS while preserving core.assets final
     * path ownership.
     *
     * @var array{
     *     carrier_key: string,
     *     asset_key: string,
     *     kind: UiAssetKind,
     *     custom_element: string,
     *     resource_path: string,
     *     source_backed_status: string,
     *     final_path_owned_by_core_assets: true
     * }
     */
    public const ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET = [
        'carrier_key' => 'admin.shell.styles',
        'asset_key' => 'admin.shell.read_only_route.css',
        'kind' => UiAssetKind::Css,
        'custom_element' => 'admin-shell',
        'resource_path' => 'resources/assets/admin-shell/read-only-route.css',
        'source_backed_status' => 'larena_owned_shell_style',
        'final_path_owned_by_core_assets' => true,
    ];

    /**
     * Shared stylesheet for older admin foundation preview surfaces while they
     * are migrated to the descriptor/runtime frontend conveyor.
     *
     * @var array{
     *     carrier_key: string,
     *     asset_key: string,
     *     kind: UiAssetKind,
     *     custom_element: string,
     *     resource_path: string,
     *     source_backed_status: string,
     *     final_path_owned_by_core_assets: true
     * }
     */
    public const ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET = [
        'carrier_key' => 'admin.foundation.preview.styles',
        'asset_key' => 'admin.foundation.preview.css',
        'kind' => UiAssetKind::Css,
        'custom_element' => 'admin-foundation-preview',
        'resource_path' => 'resources/assets/admin-foundation/preview.css',
        'source_backed_status' => 'larena_owned_foundation_preview_style',
        'final_path_owned_by_core_assets' => true,
    ];

    /**
     * Minimal source-backed Simai Framework buttons proof slice. These vendored
     * package resources are verified against the tracked provenance lock, then
     * activated through larena/core:core.assets read-only route publication.
     * They are not root app assets and do not imply full asset rollout.
     *
     * @var array<string, array{
     *     carrier_key: string,
     *     asset_key: string,
     *     kind: UiAssetKind,
     *     custom_element: string,
     *     resource_path: string,
     *     source_backed_status: string,
     *     final_path_owned_by_core_assets: true
     * }>
     */
    public const SOURCE_BACKED_SF_BUTTON_ASSETS = [
        'component_css' => [
            'carrier_key' => 'source_backed_sf.buttons.component_css',
            'asset_key' => 'source_backed_sf.buttons.component_css',
            'kind' => UiAssetKind::Css,
            'custom_element' => 'button.sf-button',
            'resource_path' => 'resources/assets/source-backed-sf/buttons/component/css/buttons.css',
            'source_backed_status' => 'source_backed_sf_component_adapter',
            'final_path_owned_by_core_assets' => true,
        ],
        'component_js' => [
            'carrier_key' => 'source_backed_sf.buttons.component_js',
            'asset_key' => 'source_backed_sf.buttons.component_js',
            'kind' => UiAssetKind::JavaScript,
            'custom_element' => 'button.sf-button',
            'resource_path' => 'resources/assets/source-backed-sf/buttons/component/js/buttons.js',
            'source_backed_status' => 'source_backed_sf_component_adapter',
            'final_path_owned_by_core_assets' => true,
        ],
        'smart_js' => [
            'carrier_key' => 'source_backed_sf.buttons.smart_js',
            'asset_key' => 'source_backed_sf.buttons.smart_js',
            'kind' => UiAssetKind::Module,
            'custom_element' => 'sf-button',
            'resource_path' => 'resources/assets/source-backed-sf/buttons/smart/js/buttons.js',
            'source_backed_status' => 'source_backed_sf_smart_component_adapter',
            'final_path_owned_by_core_assets' => true,
        ],
    ];

    /**
     * First repeatable source-backed Simai Framework catalog adapter set. Each
     * vendored slice is pinned by the larena/ui provenance lock and can be
     * activated only through larena/core:core.assets read-only publication.
     *
     * @var array<string, array<string, array{
     *     carrier_key: string,
     *     asset_key: string,
     *     kind: UiAssetKind,
     *     custom_element: string,
     *     resource_path: string,
     *     source_backed_status: string,
     *     final_path_owned_by_core_assets: true
     * }>>
     */
    public const SOURCE_BACKED_SF_CATALOG_ASSETS = [
        'buttons' => [
            'component_css' => [
                'carrier_key' => 'source_backed_sf.catalog.buttons.component_css',
                'asset_key' => 'source_backed_sf.catalog.buttons.component_css',
                'kind' => UiAssetKind::Css,
                'custom_element' => 'button.sf-button',
                'resource_path' => 'resources/assets/source-backed-sf/catalog/buttons/component/css/buttons.css',
                'source_backed_status' => 'source_backed_sf_catalog_component_adapter',
                'final_path_owned_by_core_assets' => true,
            ],
            'component_js' => [
                'carrier_key' => 'source_backed_sf.catalog.buttons.component_js',
                'asset_key' => 'source_backed_sf.catalog.buttons.component_js',
                'kind' => UiAssetKind::JavaScript,
                'custom_element' => 'button.sf-button',
                'resource_path' => 'resources/assets/source-backed-sf/catalog/buttons/component/js/buttons.js',
                'source_backed_status' => 'source_backed_sf_catalog_component_adapter',
                'final_path_owned_by_core_assets' => true,
            ],
            'component_json' => [
                'carrier_key' => 'source_backed_sf.catalog.buttons.component_json',
                'asset_key' => 'source_backed_sf.catalog.buttons.component_json',
                'kind' => UiAssetKind::Manifest,
                'custom_element' => 'button.sf-button',
                'resource_path' => 'resources/assets/source-backed-sf/catalog/buttons/component/json/button.utility.json',
                'source_backed_status' => 'source_backed_sf_catalog_component_adapter',
                'final_path_owned_by_core_assets' => true,
            ],
            'smart_js' => [
                'carrier_key' => 'source_backed_sf.catalog.buttons.smart_js',
                'asset_key' => 'source_backed_sf.catalog.buttons.smart_js',
                'kind' => UiAssetKind::Module,
                'custom_element' => 'sf-button',
                'resource_path' => 'resources/assets/source-backed-sf/catalog/buttons/smart/js/buttons.js',
                'source_backed_status' => 'source_backed_sf_catalog_smart_component_adapter',
                'final_path_owned_by_core_assets' => true,
            ],
        ],
        'tags' => [
            'component_css' => [
                'carrier_key' => 'source_backed_sf.catalog.tags.component_css',
                'asset_key' => 'source_backed_sf.catalog.tags.component_css',
                'kind' => UiAssetKind::Css,
                'custom_element' => 'span.sf-tag',
                'resource_path' => 'resources/assets/source-backed-sf/catalog/tags/component/css/tags.css',
                'source_backed_status' => 'source_backed_sf_catalog_component_adapter',
                'final_path_owned_by_core_assets' => true,
            ],
            'component_js' => [
                'carrier_key' => 'source_backed_sf.catalog.tags.component_js',
                'asset_key' => 'source_backed_sf.catalog.tags.component_js',
                'kind' => UiAssetKind::JavaScript,
                'custom_element' => 'span.sf-tag',
                'resource_path' => 'resources/assets/source-backed-sf/catalog/tags/component/js/tags.js',
                'source_backed_status' => 'source_backed_sf_catalog_component_adapter',
                'final_path_owned_by_core_assets' => true,
            ],
            'smart_js' => [
                'carrier_key' => 'source_backed_sf.catalog.tags.smart_js',
                'asset_key' => 'source_backed_sf.catalog.tags.smart_js',
                'kind' => UiAssetKind::Module,
                'custom_element' => 'sf-tag',
                'resource_path' => 'resources/assets/source-backed-sf/catalog/tags/smart/js/tags.js',
                'source_backed_status' => 'source_backed_sf_catalog_smart_component_adapter',
                'final_path_owned_by_core_assets' => true,
            ],
        ],
        'pagination' => [
            'component_css' => [
                'carrier_key' => 'source_backed_sf.catalog.pagination.component_css',
                'asset_key' => 'source_backed_sf.catalog.pagination.component_css',
                'kind' => UiAssetKind::Css,
                'custom_element' => 'nav.sf-pagination',
                'resource_path' => 'resources/assets/source-backed-sf/catalog/pagination/component/css/pagination.css',
                'source_backed_status' => 'source_backed_sf_catalog_component_adapter',
                'final_path_owned_by_core_assets' => true,
            ],
            'component_js' => [
                'carrier_key' => 'source_backed_sf.catalog.pagination.component_js',
                'asset_key' => 'source_backed_sf.catalog.pagination.component_js',
                'kind' => UiAssetKind::JavaScript,
                'custom_element' => 'nav.sf-pagination',
                'resource_path' => 'resources/assets/source-backed-sf/catalog/pagination/component/js/pagination.js',
                'source_backed_status' => 'source_backed_sf_catalog_component_adapter',
                'final_path_owned_by_core_assets' => true,
            ],
            'smart_js' => [
                'carrier_key' => 'source_backed_sf.catalog.pagination.smart_js',
                'asset_key' => 'source_backed_sf.catalog.pagination.smart_js',
                'kind' => UiAssetKind::Module,
                'custom_element' => 'sf-pagination',
                'resource_path' => 'resources/assets/source-backed-sf/catalog/pagination/smart/js/pagination.js',
                'source_backed_status' => 'source_backed_sf_catalog_smart_component_adapter',
                'final_path_owned_by_core_assets' => true,
            ],
        ],
    ];

    /**
     * Compatibility preview styles that used to be linked from root
     * public/larena assets. They stay in the shared package stylesheet until
     * the corresponding root compatibility renderers are retired or moved.
     *
     * @var array<string, array{
     *     carrier_key: string,
     *     asset_key: string,
     *     kind: UiAssetKind,
     *     custom_element: string,
     *     resource_path: string,
     *     source_backed_status: string,
     *     final_path_owned_by_core_assets: true
     * }>
     */
    public const ROOT_COMPATIBILITY_PREVIEW_STYLE_ASSETS = [
        'internal_artifact_preview' => [
            'carrier_key' => 'root.compat.internal_artifact_preview.styles',
            'asset_key' => 'root.preview.internal_artifact.css',
            'kind' => UiAssetKind::Css,
            'custom_element' => 'root-internal-artifact-preview',
            'resource_path' => 'resources/assets/admin-foundation/preview.css',
            'source_backed_status' => 'larena_owned_root_compatibility_preview_style',
            'final_path_owned_by_core_assets' => true,
        ],
        'file_operation_guarded_flow' => [
            'carrier_key' => 'root.compat.file_operation_guarded_flow.styles',
            'asset_key' => 'root.preview.file_operation_guarded_flow.css',
            'kind' => UiAssetKind::Css,
            'custom_element' => 'root-file-operation-guarded-flow',
            'resource_path' => 'resources/assets/admin-foundation/preview.css',
            'source_backed_status' => 'larena_owned_root_compatibility_preview_style',
            'final_path_owned_by_core_assets' => true,
        ],
        'guarded_data_content_runtime_bridge' => [
            'carrier_key' => 'root.compat.guarded_data_content_runtime_bridge.styles',
            'asset_key' => 'root.preview.guarded_data_content_runtime_bridge.css',
            'kind' => UiAssetKind::Css,
            'custom_element' => 'root-guarded-data-content-runtime-bridge',
            'resource_path' => 'resources/assets/admin-foundation/preview.css',
            'source_backed_status' => 'larena_owned_root_compatibility_preview_style',
            'final_path_owned_by_core_assets' => true,
        ],
    ];

    /**
     * Minimal Larena-owned fallback modules for custom elements required by the
     * admin browser smoke. They are package resources, not root app runtime.
     *
     * @var array<string, array{
     *     custom_element: string,
     *     resource_path: string,
     *     source_backed_status: string,
     *     smoke_role: string
     * }>
     */
    public const ADMIN_FRONTEND_PACKAGE_OWNED_CARRIERS = [
        'admin.menu' => [
            'custom_element' => 'sf-admin-menu',
            'resource_path' => 'resources/simai/smart/admin-menu/admin-menu.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_navigation_container',
        ],
        'admin.menu_item' => [
            'custom_element' => 'sf-admin-menu-item',
            'resource_path' => 'resources/simai/smart/admin-menu-item/admin-menu-item.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_navigation_link',
        ],
        'navigation.breadcrumbs' => [
            'custom_element' => 'sf-breadcrumbs',
            'resource_path' => 'resources/simai/smart/breadcrumbs/breadcrumbs.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_breadcrumbs',
        ],
        'data.table' => [
            'custom_element' => 'sf-table',
            'resource_path' => 'resources/assets/smart/table/table.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_table',
        ],
        'data.tree_item' => [
            'custom_element' => 'sf-tree-item',
            'resource_path' => 'resources/simai/smart/tree-item/tree-item.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_tree_item',
        ],
        'form.checkbox' => [
            'custom_element' => 'sf-checkbox',
            'resource_path' => 'resources/simai/smart/checkbox/checkbox.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_boolean_marker',
        ],
        'media.avatar' => [
            'custom_element' => 'sf-avatar',
            'resource_path' => 'resources/simai/smart/avatar/avatar.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_identity_marker',
        ],
        'navigation.pagination' => [
            'custom_element' => 'sf-pagination',
            'resource_path' => 'resources/simai/smart/pagination/pagination.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_pagination_marker',
        ],
        'status.progress_scale' => [
            'custom_element' => 'sf-progress-scale',
            'resource_path' => 'resources/simai/smart/progress-scale/progress-scale.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_progress_marker',
        ],
        'status.tag' => [
            'custom_element' => 'sf-tag',
            'resource_path' => 'resources/simai/smart/tag/tag.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_status_marker',
        ],
        'theme.toggle' => [
            'custom_element' => 'sf-toggle',
            'resource_path' => 'resources/simai/smart/toggle/toggle.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_toggle_marker',
        ],
        'toolbar.icon_button' => [
            'custom_element' => 'sf-icon-button',
            'resource_path' => 'resources/simai/smart/icon-button/icon-button.js',
            'source_backed_status' => 'larena_owned_fallback_carrier',
            'smoke_role' => 'read_only_toolbar_action_marker',
        ],
    ];

    /**
     * @param list<string> $smartComponentRefs
     * @param list<string> $distAssetRefs
     */
    public function __construct(
        public string $resourcePackKey,
        public array $smartComponentRefs,
        public array $distAssetRefs,
        public bool $activationOwnedByCoreAssets = true,
    ) {
    }

    public function isValid(): bool
    {
        return SmartComponentManifest::isStableKey($this->resourcePackKey)
            && $this->smartComponentRefs !== []
            && $this->activationOwnedByCoreAssets;
    }

    /**
     * @param list<string> $availableCustomElements
     * @return array{
     *     status: 'ready_for_browser_smoke'|'blocked_missing_required_smart_components',
     *     resource_pack_key: string,
     *     required_custom_elements: array<string, string>,
     *     available_custom_elements: list<string>,
     *     missing_custom_elements: array<string, string>,
     *     reference_carriers: array<string, string>,
     *     boundaries: array<string, bool>,
     *     carrier_source_status: array<string, array{
     *         custom_element: string,
     *         source_backed_status: string,
     *         source_kind: string,
     *         adapter_required: bool,
     *         browser_smoke_ready: bool
     *     }>
     * }
     */
    public function adminFrontendSmokeReadiness(array $availableCustomElements): array
    {
        $available = self::normalizeCustomElements($availableCustomElements);
        $missing = [];

        foreach (self::ADMIN_FRONTEND_REQUIRED_CUSTOM_ELEMENTS as $componentKey => $customElement) {
            if (!in_array($customElement, $available, true)) {
                $missing[$componentKey] = $customElement;
            }
        }

        return [
            'status' => $missing === []
                ? 'ready_for_browser_smoke'
                : 'blocked_missing_required_smart_components',
            'resource_pack_key' => $this->resourcePackKey,
            'required_custom_elements' => self::ADMIN_FRONTEND_REQUIRED_CUSTOM_ELEMENTS,
            'available_custom_elements' => $available,
            'missing_custom_elements' => $missing,
            'reference_carriers' => self::ADMIN_FRONTEND_REFERENCE_CARRIERS,
            'boundaries' => [
                'activation_owned_by_core_assets' => $this->activationOwnedByCoreAssets,
                'no_frontend_runtime_copy' => true,
                'no_root_frontend_source_of_truth' => true,
                'no_hardcoded_cdn_contract' => true,
                'no_write_methods' => true,
            ],
            'carrier_source_status' => self::adminFrontendCarrierSourceStatus(),
        ];
    }

    /**
     * @return array<string, array{
     *     custom_element: string,
     *     source_backed_status: string,
     *     source_kind: string,
     *     adapter_required: bool,
     *     browser_smoke_ready: bool
     * }>
     */
    public static function adminFrontendCarrierSourceStatus(): array
    {
        return self::ADMIN_FRONTEND_CARRIER_SOURCE_STATUS;
    }

    /**
     * @return list<string>
     */
    public static function adminFrontendCarrierBlockers(): array
    {
        return self::ADMIN_FRONTEND_BLOCKED_CARRIER_KEYS;
    }

    /**
     * @return list<string>
     */
    public static function adminFrontendCarrierSourceBlockers(): array
    {
        return self::ADMIN_FRONTEND_SOURCE_BLOCKER_KEYS;
    }

    public static function adminFrontendCarrierAssetGraph(): UiAssetGraph
    {
        $requirements = [];
        $explain = [];

        foreach (self::ADMIN_FRONTEND_CARRIER_ASSET_REQUIREMENTS as $componentKey => $asset) {
            $requirements[] = new UiAssetRequirement($asset['asset_key'], $asset['kind'], true);
            $explain[] = 'admin-carrier:' . $componentKey;
        }

        return new UiAssetGraph($requirements, $explain);
    }

    public static function adminFrontendReadOnlyRouteAssetGraph(): UiAssetGraph
    {
        $requirements = [
            ...self::adminFrontendCarrierAssetGraph()->requirements,
            new UiAssetRequirement(
                self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['asset_key'],
                self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['kind'],
                true,
            ),
            new UiAssetRequirement(
                self::ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET['asset_key'],
                self::ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET['kind'],
                true,
            ),
        ];
        $explain = [
            ...self::adminFrontendCarrierAssetGraph()->explain,
            'admin-route-style:package-owned-read-only-shell',
            'admin-foundation-preview-style:package-owned-legacy-preview-cleanup',
        ];

        foreach (self::ROOT_COMPATIBILITY_PREVIEW_STYLE_ASSETS as $asset) {
            $requirements[] = new UiAssetRequirement(
                $asset['asset_key'],
                $asset['kind'],
                true,
            );
            $explain[] = 'root-compatibility-preview-style:' . $asset['asset_key'];
        }

        return new UiAssetGraph(
            $requirements,
            $explain,
        );
    }

    /**
     * @return array<string, array{
     *     custom_element: string,
     *     resource_path: string,
     *     source_backed_status: string,
     *     smoke_role: string
     * }>
     */
    public static function adminFrontendPackageOwnedCarriers(): array
    {
        return self::ADMIN_FRONTEND_PACKAGE_OWNED_CARRIERS;
    }

    /**
     * @return list<array{
     *     carrier_key: string,
     *     asset_key: string,
     *     kind: string,
     *     custom_element: string,
     *     resource_path: string,
     *     source_backed_status: string,
     *     final_path_owned_by_core_assets: true
     * }>
     */
    public static function adminFrontendPackageOwnedCarrierPublicationAssets(): array
    {
        $assets = [];

        foreach (self::ADMIN_FRONTEND_PACKAGE_OWNED_CARRIERS as $carrierKey => $carrier) {
            $asset = self::ADMIN_FRONTEND_CARRIER_ASSET_REQUIREMENTS[$carrierKey];

            $assets[] = [
                'carrier_key' => $carrierKey,
                'asset_key' => $asset['asset_key'],
                'kind' => $asset['kind']->value,
                'custom_element' => $carrier['custom_element'],
                'resource_path' => $carrier['resource_path'],
                'source_backed_status' => $carrier['source_backed_status'],
                'final_path_owned_by_core_assets' => true,
            ];
        }

        return $assets;
    }

    /**
     * @return list<array{
     *     carrier_key: string,
     *     asset_key: string,
     *     kind: string,
     *     custom_element: string,
     *     resource_path: string,
     *     source_backed_status: string,
     *     final_path_owned_by_core_assets: true
     * }>
     */
    public static function adminFrontendReadOnlyRoutePublicationAssets(): array
    {
        $assets = [
            ...self::adminFrontendPackageOwnedCarrierPublicationAssets(),
            [
                'carrier_key' => self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['carrier_key'],
                'asset_key' => self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['asset_key'],
                'kind' => self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['kind']->value,
                'custom_element' => self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['custom_element'],
                'resource_path' => self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['resource_path'],
                'source_backed_status' => self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['source_backed_status'],
                'final_path_owned_by_core_assets' => true,
            ],
            [
                'carrier_key' => self::ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET['carrier_key'],
                'asset_key' => self::ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET['asset_key'],
                'kind' => self::ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET['kind']->value,
                'custom_element' => self::ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET['custom_element'],
                'resource_path' => self::ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET['resource_path'],
                'source_backed_status' => self::ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET['source_backed_status'],
                'final_path_owned_by_core_assets' => true,
            ],
        ];

        foreach (self::ROOT_COMPATIBILITY_PREVIEW_STYLE_ASSETS as $asset) {
            $assets[] = [
                'carrier_key' => $asset['carrier_key'],
                'asset_key' => $asset['asset_key'],
                'kind' => $asset['kind']->value,
                'custom_element' => $asset['custom_element'],
                'resource_path' => $asset['resource_path'],
                'source_backed_status' => $asset['source_backed_status'],
                'final_path_owned_by_core_assets' => true,
            ];
        }

        return $assets;
    }

    /**
     * @return list<array{
     *     carrier_key: string,
     *     asset_key: string,
     *     kind: string,
     *     custom_element: string,
     *     resource_path: string,
     *     source_backed_status: string,
     *     final_path_owned_by_core_assets: true
     * }>
     */
    public static function sourceBackedSfButtonPublicationAssets(): array
    {
        return array_map(
            static fn (array $asset): array => [
                'carrier_key' => $asset['carrier_key'],
                'asset_key' => $asset['asset_key'],
                'kind' => $asset['kind']->value,
                'custom_element' => $asset['custom_element'],
                'resource_path' => $asset['resource_path'],
                'source_backed_status' => $asset['source_backed_status'],
                'final_path_owned_by_core_assets' => true,
            ],
            array_values(self::SOURCE_BACKED_SF_BUTTON_ASSETS),
        );
    }

    /**
     * @return list<array{
     *     carrier_key: string,
     *     asset_key: string,
     *     kind: string,
     *     custom_element: string,
     *     resource_path: string,
     *     source_backed_status: string,
     *     final_path_owned_by_core_assets: true
     * }>
     */
    public static function sourceBackedSfCatalogPublicationAssets(): array
    {
        $assets = [];

        foreach (self::SOURCE_BACKED_SF_CATALOG_ASSETS as $sliceAssets) {
            foreach ($sliceAssets as $asset) {
                $assets[] = [
                    'carrier_key' => $asset['carrier_key'],
                    'asset_key' => $asset['asset_key'],
                    'kind' => $asset['kind']->value,
                    'custom_element' => $asset['custom_element'],
                    'resource_path' => $asset['resource_path'],
                    'source_backed_status' => $asset['source_backed_status'],
                    'final_path_owned_by_core_assets' => true,
                ];
            }
        }

        return $assets;
    }

    /**
     * @return array{
     *     schema: string,
     *     asset_set: string,
     *     owner_package: string,
     *     activation_owner: string,
     *     version: string,
     *     context: string,
     *     slice_count: int,
     *     resources: list<array{key: string, kind: string, path: string, load: string}>,
     *     policy: array{
     *         local_only: bool,
     *         allow_cdn: bool,
     *         allow_template_direct_include: bool,
     *         final_path_owned_by_core_assets: bool,
     *         full_asset_rollout: bool
     *     }
     * }
     */
    public static function sourceBackedSfCatalogAssetDescriptor(): array
    {
        return [
            'schema' => 'larena.core_assets.set.v1',
            'asset_set' => 'source_backed_sf.catalog_adapter_pipeline',
            'owner_package' => 'larena/ui',
            'activation_owner' => 'larena/core:core.assets',
            'version' => '0.1.0',
            'context' => 'internal_demo',
            'slice_count' => count(self::SOURCE_BACKED_SF_CATALOG_ASSETS),
            'resources' => array_map(
                static fn (array $asset): array => [
                    'key' => $asset['asset_key'],
                    'kind' => $asset['kind'],
                    'path' => $asset['resource_path'],
                    'load' => 'critical',
                ],
                self::sourceBackedSfCatalogPublicationAssets(),
            ),
            'policy' => [
                'local_only' => true,
                'allow_cdn' => false,
                'allow_template_direct_include' => false,
                'final_path_owned_by_core_assets' => true,
                'full_asset_rollout' => false,
            ],
        ];
    }

    /**
     * @return array{
     *     schema: string,
     *     asset_set: string,
     *     owner_package: string,
     *     activation_owner: string,
     *     version: string,
     *     context: string,
     *     resources: list<array{key: string, kind: string, path: string, load: string}>,
     *     policy: array{
     *         local_only: bool,
     *         allow_cdn: bool,
     *         allow_template_direct_include: bool,
     *         final_path_owned_by_core_assets: bool
     *     }
     * }
     */
    public static function sourceBackedSfButtonAssetDescriptor(): array
    {
        return [
            'schema' => 'larena.core_assets.set.v1',
            'asset_set' => 'source_backed_sf.buttons',
            'owner_package' => 'larena/ui',
            'activation_owner' => 'larena/core:core.assets',
            'version' => '0.1.0',
            'context' => 'internal_demo',
            'resources' => array_map(
                static fn (array $asset): array => [
                    'key' => $asset['asset_key'],
                    'kind' => $asset['kind']->value,
                    'path' => $asset['resource_path'],
                    'load' => 'critical',
                ],
                array_values(self::SOURCE_BACKED_SF_BUTTON_ASSETS),
            ),
            'policy' => [
                'local_only' => true,
                'allow_cdn' => false,
                'allow_template_direct_include' => false,
                'final_path_owned_by_core_assets' => true,
            ],
        ];
    }

    /**
     * @return array{
     *     schema: string,
     *     asset_set: string,
     *     owner_package: string,
     *     activation_owner: string,
     *     version: string,
     *     context: string,
     *     resources: list<array{key: string, kind: string, path: string, load: string}>,
     *     policy: array{
     *         local_only: bool,
     *         allow_cdn: bool,
     *         allow_template_direct_include: bool,
     *         final_path_owned_by_core_assets: bool
     *     }
     * }
     */
    public static function adminReadOnlyShellAssetDescriptor(): array
    {
        return [
            'schema' => 'larena.core_assets.set.v1',
            'asset_set' => 'admin.read_only_shell',
            'owner_package' => 'larena/ui',
            'activation_owner' => 'larena/core:core.assets',
            'version' => '0.1.0',
            'context' => 'admin',
            'resources' => [
                [
                    'key' => 'data.table.read_only_adapter',
                    'kind' => 'module',
                    'path' => self::ADMIN_FRONTEND_PACKAGE_OWNED_CARRIERS['data.table']['resource_path'],
                    'load' => 'critical',
                ],
                [
                    'key' => self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['asset_key'],
                    'kind' => self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['kind']->value,
                    'path' => self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['resource_path'],
                    'load' => 'critical',
                ],
                [
                    'key' => self::ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET['asset_key'],
                    'kind' => self::ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET['kind']->value,
                    'path' => self::ADMIN_FOUNDATION_PREVIEW_STYLE_ASSET['resource_path'],
                    'load' => 'critical',
                ],
                ...array_map(
                    static fn (array $asset): array => [
                        'key' => $asset['asset_key'],
                        'kind' => $asset['kind']->value,
                        'path' => $asset['resource_path'],
                        'load' => 'critical',
                    ],
                    array_values(self::ROOT_COMPATIBILITY_PREVIEW_STYLE_ASSETS),
                ),
            ],
            'policy' => [
                'local_only' => true,
                'allow_cdn' => false,
                'allow_template_direct_include' => false,
                'final_path_owned_by_core_assets' => true,
            ],
        ];
    }

    /**
     * @return array{
     *     schema: string,
     *     status: string,
     *     owners: array<string, string>,
     *     allowed_sources: list<string>,
     *     forbidden_sources: list<string>,
     *     reference_warnings: array<string, bool>,
     *     required_actions: list<string>,
     *     boundaries: array<string, bool>
     * }
     */
    public static function adminFrontendArtifactAdapterPublicationPlan(): array
    {
        return [
            'schema' => 'larena.ui.admin_frontend_artifact_adapter_publication_plan.v1',
            'status' => 'adapter_plan_ready_with_reference_warnings',
            'owners' => [
                'smart_manifest' => 'larena/ui',
                'resource_pack' => 'larena/ui',
                'asset_activation' => 'larena/core:core.assets',
                'shell_route' => 'larena/admin',
                'layout_plan' => 'larena/layout',
                'access' => 'larena/access',
            ],
            'allowed_sources' => [
                'larena/ui resource pack descriptors',
                'larena/ui package-owned smart carrier resources',
                'larena/core:core.assets final publication paths',
                'larena/admin PHP facade/backend descriptors',
                'larena/layout page descriptor and zones',
            ],
            'forbidden_sources' => [
                'ui-admin/dist copied into root simai/larena',
                'ui-admin/node_modules copied into root simai/larena',
                'hardcoded cdn.jsdelivr.net runtime dependency in Larena templates',
                'hardcoded icons.simai.io runtime dependency in Larena templates',
                'local /distr demo path as Larena runtime dependency',
                'legacy versioned framework label as Larena runtime or contract name',
            ],
            'reference_warnings' => [
                'cdn_reference_requires_core_assets_repackaging' => true,
                'legacy_versioned_framework_label_requires_larena_naming_adapter' => true,
                'ui_admin_demo_menu_requires_package_navigation_mapping' => true,
                'write_events_require_guarded_settings_or_crud_launch' => true,
                'test_actor_header_is_not_production_auth' => true,
            ],
            'required_actions' => [
                'publish smart resources through larena/core:core.assets',
                'replace demo CDN/script/style refs with package resource pack descriptors',
                'map demo menu labels/routes to larena/admin navigation descriptors',
                'keep write events deferred until guarded settings or CRUD launch',
                'run browser smoke with no CDN failures before product UI claim',
            ],
            'boundaries' => [
                'reference_only' => true,
                'root_frontend_source_of_truth' => false,
                'frontend_distribution_copy_allowed' => false,
                'node_modules_copy_allowed' => false,
                'hardcoded_cdn_allowed_in_larena_runtime' => false,
                'legacy_versioned_framework_label_contract_name_allowed' => false,
                'database_writes' => false,
                'crud_enabled' => false,
                'production_ui_claim' => false,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function adminFrontendPackageOwnedCustomElements(): array
    {
        return self::normalizeCustomElements(array_column(self::ADMIN_FRONTEND_PACKAGE_OWNED_CARRIERS, 'custom_element'));
    }

    /**
     * @return list<string>
     */
    public static function adminFrontendReferenceCustomElements(): array
    {
        return self::normalizeCustomElements(array_values(self::ADMIN_FRONTEND_REFERENCE_CARRIERS));
    }

    /**
     * @param list<string> $customElements
     * @return list<string>
     */
    private static function normalizeCustomElements(array $customElements): array
    {
        $normalized = [];

        foreach ($customElements as $customElement) {
            $customElement = strtolower(trim($customElement));
            if ($customElement === '' || !str_starts_with($customElement, 'sf-')) {
                continue;
            }
            $normalized[$customElement] = true;
        }

        return array_keys($normalized);
    }
}
