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
        if ($page->projection->rows === []) {
            $html .= $this->emptyState($emptyState);
        } else {
            $html .= '<div class="larena-dataview-scroll" role="region" tabindex="0" aria-label="' . $this->e($ariaLabel) . '">';
            $html .= '<table class="larena-table larena-dataview-table"><thead><tr>';
            foreach ($page->projection->descriptor->fields as $field) {
                if (!$field->hidden) {
                    $html .= '<th scope="col">' . $this->e($labels[$field->fieldKey] ?? $field->fieldKey) . '</th>';
                }
            }
            $html .= '</tr></thead><tbody>';
            foreach ($page->projection->rows as $row) {
                $html .= '<tr>';
                foreach ($page->projection->descriptor->fields as $field) {
                    if (!$field->hidden) {
                        $html .= '<td data-label="' . $this->e($labels[$field->fieldKey] ?? $field->fieldKey) . '">' . $this->cell($row[$field->fieldKey] ?? '') . '</td>';
                    }
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
            $html .= $this->pagination($page, $currentUrl, $labels);
        }
        $html .= '</section>';

        $requirements = $manifests['dataview']->assetRequirements;
        return new FrontendRenderArtifact(
            new BackendRenderResult($html, RenderStrategy::Native, HydrationContract::none(), $requirements),
            new UiAssetGraph($requirements, ['smart-component:admin.dataview', 'components:button,badge,toolbar,empty-state,pagination']),
            $assetActivation,
            ['manifest' => $manifests['dataview']->componentKey, 'source' => $page->projection->descriptor->source->sourceKey],
        );
    }

    private function cell(mixed $cell): string
    {
        if (!is_array($cell)) {
            return $this->e((string) $cell);
        }
        $text = $this->e((string) ($cell['text'] ?? ''));
        $type = (string) ($cell['type'] ?? 'text');
        if ($type === 'badge') {
            $tone = preg_replace('/[^a-z0-9_-]/', '', (string) ($cell['tone'] ?? 'neutral')) ?: 'neutral';
            return '<span class="larena-status larena-status-' . $tone . '" data-larena-component="admin.badge">' . $text . '</span>';
        }
        $content = !empty($cell['strong']) ? '<strong>' . $text . '</strong>' : $text;
        if (isset($cell['href'])) {
            $content = '<a class="larena-table-title" href="' . $this->e((string) $cell['href']) . '">' . $content . '</a>';
        }
        if (isset($cell['subtext'])) {
            $content .= '<br><small>' . $this->e((string) $cell['subtext']) . '</small>';
        }
        if ($type === 'code') {
            $content = '<code>' . $text . '</code>';
        }
        return $content;
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

    /** @param array<string, string> $labels */
    private function pagination(DataviewTablePage $page, string $currentUrl, array $labels): string
    {
        if ($page->pagination->lastPage() <= 1) {
            return '';
        }
        $separator = str_contains($currentUrl, '?') ? '&amp;' : '?';
        $html = '<nav class="larena-dataview-pagination" data-larena-component="admin.pagination" aria-label="' . $this->e($labels['_pagination'] ?? 'Pagination') . '">';
        for ($i = 1; $i <= $page->pagination->lastPage(); $i++) {
            $html .= '<a href="' . $this->e($currentUrl) . $separator . 'page=' . $i . '"' . ($i === $page->pagination->page ? ' aria-current="page"' : '') . '>' . $i . '</a>';
        }
        return $html . '</nav>';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
