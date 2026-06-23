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
     * Source-backed reference carriers seen in the local SIMAI Framework smart
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
        'resource_path' => 'resources/simai/smart/admin-shell/read-only-route.css',
        'source_backed_status' => 'larena_owned_shell_style',
        'final_path_owned_by_core_assets' => true,
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
            'resource_path' => 'resources/simai/smart/table/table.js',
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
        return new UiAssetGraph(
            [
                ...self::adminFrontendCarrierAssetGraph()->requirements,
                new UiAssetRequirement(
                    self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['asset_key'],
                    self::ADMIN_FRONTEND_READ_ONLY_ROUTE_STYLE_ASSET['kind'],
                    true,
                ),
            ],
            [
                ...self::adminFrontendCarrierAssetGraph()->explain,
                'admin-route-style:package-owned-read-only-shell',
            ],
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
        return [
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
