<?php

declare(strict_types=1);

namespace Larena\Ui\Contracts;

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
    ];

    /**
     * @var list<string>
     */
    public const ADMIN_FRONTEND_BLOCKED_CARRIER_KEYS = [
        'admin.menu',
        'admin.menu_item',
        'navigation.breadcrumbs',
        'data.table',
        'data.tree_item',
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
