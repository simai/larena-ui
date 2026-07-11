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
        $render = match ($key) {
            'dataview' => $this->tableRecipe($labels),
            'crud_form' => $this->crudRecipe($labels),
            'dashboard' => $this->dashboardRecipe($labels),
            'media_picker' => $this->mediaRecipe($labels),
            'settings_form' => $this->settingsRecipe($labels),
            default => throw new InvalidArgumentException('ui_lab_recipe_renderer_missing:' . $key),
        };

        return new FrontendRenderArtifact(
            $render,
            new UiAssetGraph($render->assetRequirements, ['source-backed:simai/ui-smart', 'smart-recipe:' . $key]),
            $assetActivation,
            ['kind' => 'recipe', 'key' => $key, 'layout_regions' => $this->recipeRegions($key), 'manifest' => $manifests[$key]->componentKey, 'owner_package' => 'larena/ui', 'production_ready' => false, 'all_41_packages_ready' => false],
        );
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

    private function tableRecipe(array $labels): BackendRenderResult
    {
        return Smart::render('sf-table', [
            'aria-label' => (string) ($labels['table_label'] ?? 'Pages'),
            'root-class' => 'larena-recipe-sf-table',
            'data' => [
                'columns' => [
                    ['key' => 'title', 'label' => (string) ($labels['title'] ?? 'Title')],
                    ['key' => 'status', 'label' => 'Status'],
                ],
                'rows' => [[
                    'title' => 'Larena',
                    'status' => (string) ($labels['published'] ?? 'Published'),
                ]],
                'pagination' => ['page' => 1, 'pageSize' => 10, 'total' => 1],
            ],
        ]);
    }

    private function crudRecipe(array $labels): BackendRenderResult
    {
        $title = Smart::render('sf-input', ['id' => 'recipe-title', 'name' => 'title', 'label' => (string) ($labels['title'] ?? 'Title'), 'value' => 'Larena', 'type' => 'bordered', 'size' => '1']);
        $status = Smart::render('sf-input', ['id' => 'recipe-status', 'name' => 'status', 'label' => 'Status', 'value' => 'Draft', 'type' => 'bordered', 'size' => '1']);
        $save = Smart::render('sf-button', $this->buttonProps(['label' => (string) ($labels['save'] ?? 'Save')]));
        return $this->composition('<form class="grid gap-3" data-larena-smart-composition="admin.crud_form">' . $title->html . $status->html . '<div class="flex content-main-end">' . $save->html . '</div></form>', [$title, $status, $save]);
    }

    private function dashboardRecipe(array $labels): BackendRenderResult
    {
        $metrics = [
            Smart::render('sf-badge', ['size' => '1', 'type' => 'tonal', 'scheme' => 'primary', 'text' => '12 ' . (string) ($labels['pages'] ?? 'Pages')]),
            Smart::render('sf-badge', ['size' => '1', 'type' => 'tonal', 'scheme' => 'success', 'text' => '4 ' . (string) ($labels['published'] ?? 'Published')]),
            Smart::render('sf-badge', ['size' => '1', 'type' => 'tonal', 'scheme' => 'secondary', 'text' => '3 ' . (string) ($labels['users'] ?? 'Users')]),
        ];
        return $this->composition('<section class="grid grid-col-3 gap-2 md:grid-col-1" data-larena-smart-composition="admin.dashboard">' . implode('', array_map(static fn (BackendRenderResult $render): string => $render->html, $metrics)) . '</section>', $metrics);
    }

    private function mediaRecipe(array $labels): BackendRenderResult
    {
        $items = [
            Smart::render('sf-button', ['text' => 'PNG · hero.png', 'type' => 'tonal', 'scheme' => 'primary', 'native-type' => 'button', 'aria-label' => 'hero.png']),
            Smart::render('sf-button', ['text' => 'JPG · team.jpg', 'type' => 'tonal', 'scheme' => 'secondary', 'native-type' => 'button', 'aria-label' => 'team.jpg']),
        ];
        return $this->composition('<section class="flex flex-wrap gap-2" data-larena-smart-composition="admin.media_picker" aria-label="' . $this->e((string) ($labels['media'] ?? 'Media picker')) . '">' . implode('', array_map(static fn (BackendRenderResult $render): string => $render->html, $items)) . '</section>', $items);
    }

    private function settingsRecipe(array $labels): BackendRenderResult
    {
        $name = Smart::render('sf-input', ['id' => 'recipe-site-name', 'name' => 'site_name', 'label' => (string) ($labels['site_name'] ?? 'Site name'), 'value' => 'Larena', 'type' => 'bordered', 'size' => '1']);
        $save = Smart::render('sf-button', $this->buttonProps(['label' => (string) ($labels['save'] ?? 'Save')]));
        return $this->composition('<form class="grid gap-3" data-larena-smart-composition="admin.settings_form"><fieldset class="grid gap-2"><legend>' . $this->e((string) ($labels['general'] ?? 'General')) . '</legend>' . $name->html . '</fieldset><div class="flex content-main-end">' . $save->html . '</div></form>', [$name, $save]);
    }
    private function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    private function token(string $v): string { return preg_replace('/[^a-z0-9_-]/', '', strtolower($v)) ?: 'default'; }
}
