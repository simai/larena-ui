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
        $html = match ($key) {
            'button' => $this->button($props),
            'badge' => $this->badge($props),
            'toolbar' => $this->toolbar($props),
            'empty_state' => $this->emptyState($props),
            'pagination' => $this->pagination($props),
            'field' => $this->field($props),
            'notice' => $this->notice($props),
            'modal' => $this->modal($props),
            default => throw new InvalidArgumentException('ui_lab_renderer_missing:' . $key),
        };

        return $this->artifact($manifest, $html, $assetActivation, ['kind' => 'component', 'key' => $key]);
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

    private function button(array $p): string
    {
        if (!empty($p['show_states'])) {
            $base = $p; unset($base['show_states']);
            return '<div class="larena-lab-toolbar" aria-label="Component states">'.$this->button($base).$this->button($base+['loading'=>true]).$this->button($base+['disabled'=>true]).'</div>';
        }
        $loading = (bool) ($p['loading'] ?? false); $disabled = (bool) ($p['disabled'] ?? false) || $loading;
        return '<button class="larena-button larena-button-' . $this->token((string) ($p['variant'] ?? 'primary')) . '" type="button" data-larena-smart-component="admin.button"' . ($disabled ? ' disabled aria-disabled="true"' : '') . ($loading ? ' aria-busy="true"' : '') . '>' . ($loading ? '<span aria-hidden="true">…</span> ' : '') . $this->e((string) ($p['label'] ?? 'Button')) . '</button>';
    }

    private function badge(array $p): string { return '<span class="larena-status larena-status-' . $this->token((string) ($p['tone'] ?? 'neutral')) . '" data-larena-smart-component="admin.badge">' . $this->e((string) ($p['label'] ?? 'Badge')) . '</span>'; }
    private function toolbar(array $p): string { return '<div class="larena-lab-toolbar" role="toolbar" aria-label="' . $this->e((string) ($p['aria_label'] ?? 'Actions')) . '" data-larena-smart-component="admin.toolbar"><strong>' . $this->e((string) ($p['title'] ?? 'Toolbar')) . '</strong><button class="larena-button" type="button">' . $this->e((string) ($p['action_label'] ?? 'Action')) . '</button></div>'; }
    private function emptyState(array $p): string { return '<div class="larena-empty" data-larena-smart-component="admin.empty_state"><h3>' . $this->e((string) ($p['title'] ?? 'Nothing here')) . '</h3><p>' . $this->e((string) ($p['text'] ?? '')) . '</p><a class="larena-button larena-button-primary" href="' . $this->e((string) ($p['action_href'] ?? '#')) . '">' . $this->e((string) ($p['action_label'] ?? 'Create')) . '</a></div>'; }
    private function pagination(array $p): string { $current = max(1, (int) ($p['page'] ?? 2)); $last = max($current, (int) ($p['last_page'] ?? 3)); $h = '<nav class="larena-dataview-pagination" aria-label="' . $this->e((string) ($p['aria_label'] ?? 'Pagination')) . '" data-larena-smart-component="admin.pagination">'; for ($i=1;$i<=$last;$i++) {$h .= '<a href="#page-' . $i . '"' . ($i === $current ? ' aria-current="page"' : '') . '>' . $i . '</a>';} return $h . '</nav>'; }
    private function field(array $p): string { if(!empty($p['show_states'])){$base=$p;unset($base['show_states'],$base['error']);return '<div class="larena-form">'.$this->field($base).$this->field($base+['disabled'=>true]).$this->field($base+['error'=>(string)($p['error']??'Error')]).'</div>';} $id='lab-field-'.$this->token((string)($p['name']??'field')).(!empty($p['disabled'])?'-disabled':((string)($p['error']??'')!==''?'-error':'')); $error=(string)($p['error']??''); return '<div class="larena-field" data-larena-smart-component="admin.field"><label for="'.$id.'">'.$this->e((string)($p['label']??'Field')).'</label><input id="'.$id.'" name="'.$this->e((string)($p['name']??'field')).'" type="'.$this->token((string)($p['type']??'text')).'" value="'.$this->e((string)($p['value']??'')) . '"' . (!empty($p['required'])?' required':'') . (!empty($p['disabled'])?' disabled':'') . ($error!==''?' aria-invalid="true" aria-describedby="'.$id.'-error"':'') . '>' . ($error!==''?'<span id="'.$id.'-error" class="larena-field-error">'.$this->e($error).'</span>':'') . '</div>'; }
    private function notice(array $p): string { $error=($p['tone']??'success')==='error'; return '<div class="larena-notice larena-notice-'.($error?'error':'success').'" role="'.($error?'alert':'status').'" data-larena-smart-component="admin.notice">'.$this->e((string)($p['message']??'Notice')).'</div>'; }
    private function modal(array $p): string { $id=$this->token((string)($p['id']??'lab-dialog')); return '<div data-larena-smart-component="admin.modal"><button class="larena-button" type="button" data-larena-dialog-open="'.$id.'">'.$this->e((string)($p['trigger_label']??'Open dialog')).'</button><dialog id="'.$id.'" class="larena-dialog" aria-labelledby="'.$id.'-title"><h3 id="'.$id.'-title">'.$this->e((string)($p['title']??'Dialog')).'</h3><p>'.$this->e((string)($p['description']??'')).'</p><button class="larena-button" type="button" data-larena-dialog-close>'.$this->e((string)($p['close_label']??'Close')).'</button></dialog></div>'; }
    private function tableRecipe(array $l): string { return '<div data-larena-smart-component="admin.dataview"><div class="larena-dataview-scroll" role="region" tabindex="0" aria-label="'.$this->e((string)($l['table_label']??'Pages')).'"><table class="larena-table"><thead><tr><th scope="col">'. $this->e((string)($l['title']??'Title')).'</th><th scope="col">Status</th></tr></thead><tbody><tr><td><a href="/admin/docara/pages">Larena</a></td><td><span class="larena-status larena-status-published">Published</span></td></tr></tbody></table></div></div>'; }
    private function crudRecipe(array $l): string { return '<form class="larena-form" data-larena-smart-component="admin.crud_form"><div class="larena-field"><label for="recipe-title">'.$this->e((string)($l['title']??'Title')).'</label><input id="recipe-title" value="Larena"></div><div class="larena-field"><label for="recipe-status">Status</label><select id="recipe-status"><option>Draft</option><option>Published</option></select></div><div class="larena-form-actions"><button class="larena-button larena-button-primary" type="button">'.$this->e((string)($l['save']??'Save')).'</button></div></form>'; }
    private function dashboardRecipe(array $l): string { return '<section class="larena-lab-dashboard" data-larena-smart-component="admin.dashboard"><article><strong>12</strong><span>'.$this->e((string)($l['pages']??'Pages')).'</span></article><article><strong>4</strong><span>'.$this->e((string)($l['published']??'Published')).'</span></article><article><strong>3</strong><span>'.$this->e((string)($l['users']??'Users')).'</span></article></section>'; }
    private function mediaRecipe(array $l): string { return '<section class="larena-media-grid" data-larena-smart-component="admin.media_picker" aria-label="'.$this->e((string)($l['media']??'Media picker')).'"><button class="larena-media-card larena-lab-media-option" type="button" aria-pressed="true"><span class="larena-media-card__preview">PNG</span><strong>hero.png</strong></button><button class="larena-media-card larena-lab-media-option" type="button" aria-pressed="false"><span class="larena-media-card__preview">JPG</span><strong>team.jpg</strong></button></section>'; }
    private function settingsRecipe(array $l): string { return '<form class="larena-form" data-larena-smart-component="admin.settings_form"><fieldset><legend>'.$this->e((string)($l['general']??'General')).'</legend><div class="larena-field"><label for="recipe-site-name">'.$this->e((string)($l['site_name']??'Site name')).'</label><input id="recipe-site-name" value="Larena"></div></fieldset><div class="larena-form-actions"><button class="larena-button larena-button-primary" type="button">'.$this->e((string)($l['save']??'Save')).'</button></div></form>'; }
    private function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    private function token(string $v): string { return preg_replace('/[^a-z0-9_-]/', '', strtolower($v)) ?: 'default'; }
}
