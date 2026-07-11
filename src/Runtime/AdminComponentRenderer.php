<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use InvalidArgumentException;
use Larena\Ui\Components\AdminComponentCatalog;
use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\FrontendRenderArtifact;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Contracts\SmartComponentManifest;
use Larena\Ui\Contracts\UiAssetGraph;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Enums\HydrationStrategy;
use Larena\Ui\Smart;

final class AdminComponentRenderer
{
    public function __construct(private readonly AdminComponentCatalog $catalog = new AdminComponentCatalog()) {}

    /** @param array<string, mixed> $props @param array<string, mixed> $assetActivation */
    public function component(string $key, array $props, array $assetActivation): FrontendRenderArtifact
    {
        $definitions = $this->catalog->definitions();
        if (!isset($definitions[$key])) {
            throw new InvalidArgumentException('ui_lab_unknown_component:' . $key);
        }
        /** @var SmartComponentManifest $manifest */
        $manifest = $definitions[$key]['manifest'];
        $render = match ($key) {
            'button' => $this->buttonRender($props),
            'badge' => $this->badgeRender($props),
            'toolbar' => $this->toolbarRender($props),
            'empty_state' => $this->emptyStateRender($props),
            'pagination' => $this->paginationRender($props),
            'field' => $this->fieldRender($props),
            'notice' => $this->noticeRender($props),
            'modal' => $this->modalRender($props),
            default => throw new InvalidArgumentException('ui_lab_renderer_missing:' . $key),
        };

        return new FrontendRenderArtifact(
            $render,
            new UiAssetGraph($render->assetRequirements, ['source-backed:simai/ui-smart', 'atlas-component:' . $key]),
            $assetActivation,
            ['kind' => 'component', 'key' => $key, 'manifest' => $manifest->componentKey, 'runtime_tags' => $this->runtimeTags($key), 'owner_package' => 'larena/ui', 'production_ready' => false, 'all_41_packages_ready' => false],
        );
    }

    /** @param array<string, mixed> $labels @param array<string, mixed> $assetActivation */
    public function recipe(string $key, array $labels, array $assetActivation): FrontendRenderArtifact
    {
        $manifests = $this->catalog->manifests();
        if (!isset($manifests[$key])) {
            throw new InvalidArgumentException('ui_lab_unknown_recipe:' . $key);
        }
        $html = match ($key) {
            'dataview' => $this->tableRecipe($labels),
            'crud_form' => $this->crudRecipe($labels),
            'dashboard' => $this->dashboardRecipe($labels),
            'media_picker' => $this->mediaRecipe($labels),
            'settings_form' => $this->settingsRecipe($labels),
            default => throw new InvalidArgumentException('ui_lab_recipe_renderer_missing:' . $key),
        };

        return $this->artifact($manifests[$key], $html, $assetActivation, ['kind' => 'recipe', 'key' => $key, 'layout_regions' => $this->recipeRegions($key)]);
    }

    /** @return list<string> */
    public function recipeRegions(string $key): array
    {
        return match ($key) {
            'dashboard' => ['topbar', 'sidebar', 'workspace', 'notifications'],
            'media_picker' => ['workspace', 'drawer', 'modal', 'notifications'],
            'crud_form', 'settings_form' => ['workspace', 'notifications'],
            default => ['workspace'],
        };
    }

    private function artifact(SmartComponentManifest $manifest, string $html, array $activation, array $diagnostics): FrontendRenderArtifact
    {
        return new FrontendRenderArtifact(
            new BackendRenderResult($html, RenderStrategy::Native, HydrationContract::none(), $manifest->assetRequirements),
            new UiAssetGraph($manifest->assetRequirements, ['smart-component:' . $manifest->componentKey, 'package-owned:larena/ui']),
            $activation,
            $diagnostics + ['manifest' => $manifest->componentKey, 'owner_package' => 'larena/ui', 'production_ready' => false, 'all_41_packages_ready' => false],
        );
    }

    private function buttonRender(array $p): BackendRenderResult
    {
        if (!empty($p['show_states'])) {
            $base = $p; unset($base['show_states']);
            $renders = [
                Smart::render('sf-button', $this->buttonProps($base)),
                Smart::render('sf-button', $this->buttonProps($base + ['loading' => true])),
                Smart::render('sf-button', $this->buttonProps($base + ['disabled' => true])),
            ];
            $html = '<div class="larena-lab-toolbar" aria-label="Component states">' . implode('', array_map(static fn (BackendRenderResult $render): string => $render->html, $renders)) . '</div>';
            return new BackendRenderResult(
                $html,
                RenderStrategy::Host,
                new HydrationContract(HydrationStrategy::Adopt, hash('sha256', $html), 'stable-hosts', true),
                $renders[0]->assetRequirements,
            );
        }
        return Smart::render('sf-button', $this->buttonProps($p));
    }

    /** @return array<string, mixed> */
    private function buttonProps(array $props): array
    {
        $variant = (string) ($props['variant'] ?? 'primary');
        return [
            'text' => (string) ($props['label'] ?? 'Button'),
            'type' => $variant === 'secondary' ? 'tonal' : ($variant === 'outline' ? 'outline' : 'default'),
            'scheme' => $variant === 'secondary' ? 'secondary' : 'primary',
            'loading' => (bool) ($props['loading'] ?? false),
            'disabled' => (bool) ($props['disabled'] ?? false) || (bool) ($props['loading'] ?? false),
            'native-type' => 'button',
        ];
    }

    private function badgeRender(array $props): BackendRenderResult
    {
        $tone = (string) ($props['tone'] ?? 'neutral');
        return Smart::render('sf-badge', [
            'size' => '1/2',
            'type' => 'tonal',
            'scheme' => $tone === 'published' ? 'success' : ($tone === 'error' ? 'error' : 'neutral'),
            'text' => (string) ($props['label'] ?? 'Badge'),
        ]);
    }

    private function toolbarRender(array $props): BackendRenderResult
    {
        $action = Smart::render('sf-button', $this->buttonProps(['label' => (string) ($props['action_label'] ?? 'Action')]));
        $html = '<div class="larena-lab-toolbar" role="toolbar" aria-label="' . $this->e((string) ($props['aria_label'] ?? 'Actions')) . '" data-larena-smart-composition="admin.toolbar"><strong>' . $this->e((string) ($props['title'] ?? 'Toolbar')) . '</strong>' . $action->html . '</div>';
        return $this->composition($html, [$action]);
    }

    private function emptyStateRender(array $props): BackendRenderResult
    {
        $action = Smart::render('sf-button', $this->buttonProps(['label' => (string) ($props['action_label'] ?? 'Create')]));
        $html = '<div class="larena-empty" data-larena-smart-composition="admin.empty_state"><h3>' . $this->e((string) ($props['title'] ?? 'Nothing here')) . '</h3><p>' . $this->e((string) ($props['text'] ?? '')) . '</p>' . $action->html . '</div>';
        return $this->composition($html, [$action]);
    }

    private function paginationRender(array $props): BackendRenderResult
    {
        return Smart::render('sf-pagination', [
            'current' => max(1, (int) ($props['page'] ?? 1)),
            'total' => max(1, (int) ($props['last_page'] ?? 1)),
            'bottom' => false,
            'aria-label' => (string) ($props['aria_label'] ?? 'Pagination'),
        ]);
    }

    private function fieldRender(array $props): BackendRenderResult
    {
        if (!empty($props['show_states'])) {
            $base = $props;
            unset($base['show_states'], $base['error']);
            $renders = [
                $this->fieldRender($base),
                $this->fieldRender($base + ['disabled' => true]),
                $this->fieldRender($base + ['error' => (string) ($props['error'] ?? 'Error')]),
            ];
            return $this->composition('<div class="larena-form" data-larena-smart-composition="admin.field.states">' . implode('', array_map(static fn (BackendRenderResult $render): string => $render->html, $renders)) . '</div>', $renders);
        }
        $error = (string) ($props['error'] ?? '');
        return Smart::render('sf-input', [
            'id' => 'lab-field-' . $this->token((string) ($props['name'] ?? 'field')) . ($error !== '' ? '-error' : (!empty($props['disabled']) ? '-disabled' : '')),
            'name' => (string) ($props['name'] ?? 'field'),
            'label' => (string) ($props['label'] ?? 'Field'),
            'input-type' => (string) ($props['type'] ?? 'text'),
            'value' => (string) ($props['value'] ?? ''),
            'required' => (bool) ($props['required'] ?? false),
            'disabled' => (bool) ($props['disabled'] ?? false),
            'error' => $error !== '',
            'hint' => $error,
            'type' => 'bordered',
            'size' => '1',
        ]);
    }

    private function noticeRender(array $props): BackendRenderResult
    {
        $error = ($props['tone'] ?? 'success') === 'error';
        return Smart::render('sf-alert', [
            'type' => $error ? 'danger' : 'clear',
            'variant' => 'default',
            'title' => $error ? 'Error' : 'Success',
            'supporting-text' => (string) ($props['message'] ?? 'Notice'),
        ]);
    }

    private function modalRender(array $props): BackendRenderResult
    {
        $id = $this->token((string) ($props['id'] ?? 'lab-modal'));
        $trigger = Smart::render('sf-button', $this->buttonProps(['label' => (string) ($props['trigger_label'] ?? 'Open dialog')]));
        $modal = Smart::render('sf-modal', [
            'id' => $id,
            'modal-id' => $id,
            'title' => (string) ($props['title'] ?? 'Dialog'),
            'text' => (string) ($props['description'] ?? ''),
            'overlay' => true,
        ]);
        $html = '<div data-larena-smart-composition="admin.modal"><span data-larena-sf-modal-trigger="' . $this->e($id) . '">' . $trigger->html . '</span>' . $modal->html . '</div>';
        return $this->composition($html, [$trigger, $modal]);
    }

    /** @param list<BackendRenderResult> $renders */
    private function composition(string $html, array $renders): BackendRenderResult
    {
        $requirements = [];
        foreach ($renders as $render) {
            foreach ($render->assetRequirements as $requirement) {
                $requirements[$requirement->assetKey] = $requirement;
            }
        }
        return new BackendRenderResult($html, RenderStrategy::Host, new HydrationContract(HydrationStrategy::Adopt, hash('sha256', $html), 'stable-hosts', true), array_values($requirements));
    }

    /** @return list<string> */
    private function runtimeTags(string $key): array
    {
        return match ($key) {
            'button' => ['sf-button'],
            'badge' => ['sf-badge'],
            'toolbar', 'empty_state' => ['sf-button'],
            'pagination' => ['sf-pagination'],
            'field' => ['sf-input'],
            'notice' => ['sf-alert'],
            'modal' => ['sf-button', 'sf-modal'],
            default => [],
        };
    }

    private function tableRecipe(array $l): string { return '<div data-larena-smart-component="admin.dataview"><div class="larena-dataview-scroll" role="region" tabindex="0" aria-label="'.$this->e((string)($l['table_label']??'Pages')).'"><table class="larena-table"><thead><tr><th scope="col">'. $this->e((string)($l['title']??'Title')).'</th><th scope="col">Status</th></tr></thead><tbody><tr><td><a href="/admin/docara/pages">Larena</a></td><td><span class="larena-status larena-status-published">Published</span></td></tr></tbody></table></div></div>'; }
    private function crudRecipe(array $l): string { return '<form class="larena-form" data-larena-smart-component="admin.crud_form"><div class="larena-field"><label for="recipe-title">'.$this->e((string)($l['title']??'Title')).'</label><input id="recipe-title" value="Larena"></div><div class="larena-field"><label for="recipe-status">Status</label><select id="recipe-status"><option>Draft</option><option>Published</option></select></div><div class="larena-form-actions"><button class="larena-button larena-button-primary" type="button">'.$this->e((string)($l['save']??'Save')).'</button></div></form>'; }
    private function dashboardRecipe(array $l): string { return '<section class="larena-lab-dashboard" data-larena-smart-component="admin.dashboard"><article><strong>12</strong><span>'.$this->e((string)($l['pages']??'Pages')).'</span></article><article><strong>4</strong><span>'.$this->e((string)($l['published']??'Published')).'</span></article><article><strong>3</strong><span>'.$this->e((string)($l['users']??'Users')).'</span></article></section>'; }
    private function mediaRecipe(array $l): string { return '<section class="larena-media-grid" data-larena-smart-component="admin.media_picker" aria-label="'.$this->e((string)($l['media']??'Media picker')).'"><button class="larena-media-card larena-lab-media-option" type="button" aria-pressed="true"><span class="larena-media-card__preview">PNG</span><strong>hero.png</strong></button><button class="larena-media-card larena-lab-media-option" type="button" aria-pressed="false"><span class="larena-media-card__preview">JPG</span><strong>team.jpg</strong></button></section>'; }
    private function settingsRecipe(array $l): string { return '<form class="larena-form" data-larena-smart-component="admin.settings_form"><fieldset><legend>'.$this->e((string)($l['general']??'General')).'</legend><div class="larena-field"><label for="recipe-site-name">'.$this->e((string)($l['site_name']??'Site name')).'</label><input id="recipe-site-name" value="Larena"></div></fieldset><div class="larena-form-actions"><button class="larena-button larena-button-primary" type="button">'.$this->e((string)($l['save']??'Save')).'</button></div></form>'; }
    private function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    private function token(string $v): string { return preg_replace('/[^a-z0-9_-]/', '', strtolower($v)) ?: 'default'; }
}
