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
     *     boundaries: array<string, bool>
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
        ];
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
