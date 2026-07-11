<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use InvalidArgumentException;
use Larena\Dataview\Contracts\DataviewTablePage;
use Larena\Ui\Components\AdminComponentCatalog;
use Larena\Ui\Contracts\BackendRenderResult;
use Larena\Ui\Contracts\FrontendRenderArtifact;
use Larena\Ui\Contracts\HydrationContract;
use Larena\Ui\Contracts\UiAssetGraph;
use Larena\Ui\Enums\RenderStrategy;
use Larena\Ui\Smart;

final class AdminDataviewRenderer
{
    public function __construct(private readonly AdminComponentCatalog $catalog = new AdminComponentCatalog()) {}

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
        $manifests = $this->catalog->manifests();
        foreach ($manifests as $manifest) {
            if (!$manifest->isValid()) {
                throw new InvalidArgumentException('ui_admin_component_manifest_invalid:' . $manifest->componentKey);
            }
        }

        $html = '<section class="larena-panel larena-dataview" aria-label="' . $this->e($ariaLabel) . '" data-larena-smart-component="admin.dataview">';
        $smartRender = null;
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
            $smartRender = Smart::render('sf-table', [
                'aria-label' => $ariaLabel,
                'root-class' => 'larena-pages-sf-table',
                'data' => [
                    'columns' => $columns,
                    'rows' => $rows,
                    'pagination' => [
                        'page' => $page->pagination->page,
                        'pageSize' => $page->pagination->perPage,
                        'total' => $page->pagination->total,
                    ],
                ],
            ]);
            $html .= $smartRender->html;
        }
        $html .= '</section>';

        $requirements = $smartRender === null ? $manifests['dataview']->assetRequirements : $smartRender->assetRequirements;
        $backendRender = $smartRender === null
            ? new BackendRenderResult($html, RenderStrategy::Native, HydrationContract::none(), $requirements)
            : new BackendRenderResult($html, $smartRender->strategy, $smartRender->hydration, $requirements);
        return new FrontendRenderArtifact(
            $backendRender,
            new UiAssetGraph($requirements, ['smart-component:sf-table', 'source-backed:simai/ui-smart', 'larena-view:admin.dataview']),
            $assetActivation,
            ['manifest' => $manifests['dataview']->componentKey, 'source' => $page->projection->descriptor->source->sourceKey, 'runtime_tag' => $smartRender === null ? null : 'sf-table'],
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
