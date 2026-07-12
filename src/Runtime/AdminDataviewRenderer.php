<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use InvalidArgumentException;
use Larena\Dataview\Contracts\DataviewTablePage;
use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\FrontendRenderArtifact;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Contracts\UiAssetGraph;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Registry\SmartRegistry;

final class AdminDataviewRenderer
{
    private readonly SmartRegistry $registry;
    private readonly SmartManager $smart;

    public function __construct(?SmartRegistry $registry = null, ?SmartManager $smart = null)
    {
        $this->registry = $registry ?? SmartRegistry::withDefaults();
        $this->smart = $smart ?? new SmartManager($this->registry);
    }

    /**
     * @param array<string, string> $labels
     * @param array<string, mixed> $emptyState
     * @param array<string, mixed> $assetActivation
     */
    public function render(DataviewTablePage $page, array $labels, array $emptyState, string $ariaLabel, string $currentUrl, array $assetActivation): FrontendRenderArtifact
    {
        if (!$page->isSafeForRender()) {
            throw new InvalidArgumentException('ui_admin_dataview_unsafe_projection');
        }
        $manifest = $this->registry->manifest('ui.dataview');
        if (!$manifest->isCanonical()) {
            throw new InvalidArgumentException('ui_admin_component_manifest_invalid:' . $manifest->componentKey);
        }
        $this->registry->renderer($manifest->rendererId);

        $html = '<section class="larena-panel larena-dataview" aria-label="' . $this->e($ariaLabel) . '" data-larena-smart-component="admin.dataview">';
        $smartArtifact = null;
        if ($page->projection->rows === []) {
            $html .= $this->emptyState($emptyState);
        } else {
            $columns = [];
            foreach ($page->projection->descriptor->fields as $field) {
                if (!$field->hidden) {
                    $column = ['key' => $field->fieldKey, 'label' => $labels[$field->fieldKey] ?? $field->fieldKey];
                    foreach ($page->projection->rows as $candidate) {
                        $cell = $candidate[$field->fieldKey] ?? null;
                        if (is_array($cell) && isset($cell['href'])) {
                            $column['renderer'] = 'link';
                            break;
                        }
                    }
                    $columns[] = $column;
                }
            }
            $rows = [];
            foreach ($page->projection->rows as $row) {
                $smartRow = [];
                foreach ($page->projection->descriptor->fields as $field) {
                    if (!$field->hidden) {
                        $smartRow[$field->fieldKey] = $this->smartCell($row[$field->fieldKey] ?? '');
                    }
                }
                $rows[] = $smartRow;
            }
            $smartArtifact = $this->smart->render('ui.dataview', [
                'aria-label' => $ariaLabel,
                'data' => [
                    'columns' => $columns,
                    'rows' => $rows,
                    'pagination' => [
                        'page' => $page->pagination->page,
                        'pageSize' => $page->pagination->perPage,
                        'total' => $page->pagination->total,
                    ],
                ],
            ], $assetActivation);
            $html .= $smartArtifact->html();
        }
        $html .= '</section>';

        $requirements = $smartArtifact === null ? $manifest->assetRequirements : $smartArtifact->render->assetRequirements;
        $backendRender = $smartArtifact === null
            ? new BackendRenderResult($html, RenderStrategy::Native, HydrationContract::none(), $requirements)
            : new BackendRenderResult($html, $smartArtifact->render->strategy, $smartArtifact->render->hydration, $requirements);
        return new FrontendRenderArtifact(
            $backendRender,
            new UiAssetGraph($requirements, ['smart-component:ui.dataview', 'frontend-tag:sf-table', 'source-backed:simai/ui-smart', 'larena-view:admin.dataview']),
            $assetActivation,
            [
                'manifest' => $manifest->componentKey,
                'source' => $page->projection->descriptor->source->sourceKey,
                'runtime_tag' => $smartArtifact === null ? null : 'sf-table',
                'smart_manager' => $smartArtifact === null ? null : $smartArtifact->toArray()['diagnostics'],
            ],
        );
    }

    private function smartCell(mixed $cell): mixed
    {
        if (!is_array($cell)) {
            return $cell;
        }
        if (isset($cell['href'])) {
            return ['href' => (string) $cell['href'], 'text' => (string) ($cell['text'] ?? '')];
        }
        return (string) ($cell['text'] ?? '');
    }

    /** @param array<string, mixed> $state */
    private function emptyState(array $state): string
    {
        $html = '<div class="larena-empty" data-larena-component="admin.empty_state"><h2>' . $this->e((string) ($state['title'] ?? '')) . '</h2><p>' . $this->e((string) ($state['text'] ?? '')) . '</p>';
        if (isset($state['action_href'], $state['action_label'])) {
            $html .= '<a class="larena-button larena-button-primary" data-larena-component="admin.button" href="' . $this->e((string) $state['action_href']) . '">' . $this->e((string) $state['action_label']) . '</a>';
        }
        return $html . '</div>';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
