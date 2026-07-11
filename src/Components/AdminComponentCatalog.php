<?php

declare(strict_types=1);

namespace Larena\Ui\Components;

use Larena\Ui\Assets\AdminDataviewAssetManifest;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\UiAssetRequirement;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Enums\UiAssetKind;

final class AdminComponentCatalog
{
    /** @return array<string, SmartComponentManifest> */
    public function manifests(): array
    {
        $asset = [new UiAssetRequirement(AdminDataviewAssetManifest::ASSET_KEY, UiAssetKind::Css, true)];
        return [
            'button' => new SmartComponentManifest('admin.button', $this->schema(['label' => 'string', 'variant' => 'string', 'disabled' => 'boolean', 'loading' => 'boolean', 'show_states' => 'boolean']), [], RenderStrategy::Native, $asset),
            'badge' => new SmartComponentManifest('admin.badge', $this->schema(['label' => 'string', 'tone' => 'string']), [], RenderStrategy::Native, $asset),
            'toolbar' => new SmartComponentManifest('admin.toolbar', $this->schema(['aria_label' => 'string', 'title' => 'string']), ['actions'], RenderStrategy::Native, $asset),
            'empty_state' => new SmartComponentManifest('admin.empty_state', $this->schema(['title' => 'string', 'text' => 'string', 'action_label' => 'string', 'action_href' => 'string']), ['actions'], RenderStrategy::Native, $asset),
            'pagination' => new SmartComponentManifest('admin.pagination', $this->schema(['page' => 'integer', 'last_page' => 'integer', 'aria_label' => 'string']), [], RenderStrategy::Native, $asset),
            'field' => new SmartComponentManifest('admin.field', $this->schema(['name' => 'string', 'label' => 'string', 'type' => 'string', 'value' => 'string', 'required' => 'boolean', 'disabled' => 'boolean', 'error' => 'string', 'show_states' => 'boolean']), [], RenderStrategy::Native, $asset),
            'notice' => new SmartComponentManifest('admin.notice', $this->schema(['message' => 'string', 'tone' => 'string']), [], RenderStrategy::Native, $asset),
            'modal' => new SmartComponentManifest('admin.modal', $this->schema(['id' => 'string', 'title' => 'string', 'description' => 'string', 'trigger_label' => 'string', 'close_label' => 'string']), ['footer'], RenderStrategy::Native, $asset),
            'dataview' => new SmartComponentManifest('admin.dataview', $this->schema(['aria_label' => 'string', 'source' => 'string']), ['toolbar', 'table', 'empty_state', 'pagination'], RenderStrategy::Native, $asset),
            'crud_form' => new SmartComponentManifest('admin.crud_form', $this->schema(['title' => 'string', 'submit_label' => 'string']), ['fields', 'actions', 'notifications'], RenderStrategy::Native, $asset),
            'dashboard' => new SmartComponentManifest('admin.dashboard', $this->schema(['title' => 'string']), ['toolbar', 'metrics', 'content'], RenderStrategy::Native, $asset),
            'media_picker' => new SmartComponentManifest('admin.media_picker', $this->schema(['title' => 'string', 'multiple' => 'boolean']), ['toolbar', 'items', 'empty_state'], RenderStrategy::Native, $asset),
            'settings_form' => new SmartComponentManifest('admin.settings_form', $this->schema(['title' => 'string', 'submit_label' => 'string']), ['groups', 'actions', 'notifications'], RenderStrategy::Native, $asset),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        $manifests = $this->manifests();
        $common = ['owner_package' => 'larena/ui', 'states' => ['default'], 'product_href' => '/admin'];

        return [
            'button' => array_merge($common, ['manifest' => $manifests['button'], 'states' => ['default', 'loading', 'disabled'], 'accessibility' => ['native_button', 'disabled_semantics', 'visible_focus'], 'product_href' => '/admin/docara/pages/create']),
            'badge' => array_merge($common, ['manifest' => $manifests['badge'], 'states' => ['neutral', 'published', 'error'], 'accessibility' => ['text_not_color_only'], 'product_href' => '/admin/docara/pages']),
            'toolbar' => array_merge($common, ['manifest' => $manifests['toolbar'], 'accessibility' => ['labelled_region', 'logical_tab_order'], 'product_href' => '/admin/docara/pages']),
            'empty_state' => array_merge($common, ['manifest' => $manifests['empty_state'], 'accessibility' => ['heading_structure', 'descriptive_action'], 'product_href' => '/admin/docara/pages']),
            'pagination' => array_merge($common, ['manifest' => $manifests['pagination'], 'states' => ['default', 'current_page'], 'accessibility' => ['labelled_navigation', 'aria_current'], 'product_href' => '/admin/docara/pages']),
            'field' => array_merge($common, ['manifest' => $manifests['field'], 'states' => ['default', 'required', 'disabled', 'error'], 'accessibility' => ['explicit_label', 'aria_describedby', 'aria_invalid'], 'product_href' => '/admin/docara/pages/create']),
            'notice' => array_merge($common, ['manifest' => $manifests['notice'], 'states' => ['success', 'error'], 'accessibility' => ['status_or_alert_role'], 'product_href' => '/admin/docara/pages/create']),
            'modal' => array_merge($common, ['manifest' => $manifests['modal'], 'states' => ['closed', 'open'], 'accessibility' => ['native_dialog', 'labelled_dialog', 'escape_close', 'focus_return'], 'product_href' => '/admin/files']),
        ];
    }

    /** @param array<string, string> $properties */
    private function schema(array $properties): array
    {
        return ['type' => 'object', 'properties' => array_map(static fn (string $type): array => ['type' => $type], $properties), 'additionalProperties' => false];
    }
}
