<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use InvalidArgumentException;
use Larena\Dataview\Contracts\DataviewTablePage;
use Larena\Layout\Contracts\AdminCollectionLayoutPlan;
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
    public function render(DataviewTablePage $page, array $labels, array $emptyState, string $ariaLabel, string $currentUrl, array $assetActivation, ?array $frameworkPlan = null): FrontendRenderArtifact
    {
        if (!$page->isSafeForRender()) {
            throw new InvalidArgumentException('ui_admin_dataview_unsafe_projection');
        }
        $manifest = $this->registry->manifest('ui.dataview');
        if (!$manifest->isCanonical()) {
            throw new InvalidArgumentException('ui_admin_component_manifest_invalid:' . $manifest->componentKey);
        }
        $this->registry->renderer($manifest->rendererId);
        $frameworkDiagnostics = $this->frameworkDiagnostics($frameworkPlan, $manifest->frontendTag);

        $utilityRegions = AdminCollectionLayoutPlan::frameworkUtilityRegions();
        $collectionUtilityClasses = $frameworkPlan === null ? '' : ' ' . implode(' ', array_merge(...array_values($utilityRegions['collection'])));
        $contentUtilityClasses = $frameworkPlan === null ? '' : ' ' . implode(' ', array_merge(...array_values($utilityRegions['content'])));
        $frameworkAttributes = $frameworkPlan === null ? ''
            : ' data-framework-recipe="admin.collection"'
                . ' data-framework-registry-sha256="' . $this->e((string) $frameworkPlan['registry_sha256']) . '"'
                . ' data-framework-plan-sha256="' . $this->e((string) $frameworkPlan['plan_sha256']) . '"';
        $html = '<section class="larena-panel larena-dataview' . $collectionUtilityClasses . '" aria-label="' . $this->e($ariaLabel) . '" data-larena-smart-component="admin.dataview" data-larena-read-only="true"' . $frameworkAttributes . '>';
        $html .= '<div class="larena-dataview-content' . $contentUtilityClasses . '" data-framework-region="content" role="region" tabindex="0" aria-label="' . $this->e($ariaLabel) . '">';
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
                'selectable' => false,
                'settings' => false,
                'actions' => false,
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
        $html .= '</div></section>';

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
                'framework_contract' => $frameworkDiagnostics,
                'row_count' => count($page->projection->rows),
                'read_only_controls_suppressed' => true,
            ],
        );
    }

    /** @param array<string, mixed>|null $plan @return array<string, mixed>|null */
    private function frameworkDiagnostics(?array $plan, ?string $runtimeTag): ?array
    {
        if ($plan === null) {
            return null;
        }
        $entries = is_array($plan['entries'] ?? null) ? $plan['entries'] : [];
        $smartTable = null;
        foreach ($entries as $entry) {
            if (is_array($entry) && ($entry['id'] ?? null) === 'smart.table') {
                $smartTable = $entry;
                break;
            }
        }
        if ($smartTable === null
            || ($smartTable['kind'] ?? null) !== 'smart-component'
            || !is_array($smartTable['runtime'] ?? null)
            || !in_array($runtimeTag, $smartTable['runtime']['tags'] ?? [], true)
            || ($plan['effects_allowed'] ?? null) !== false
        ) {
            throw new InvalidArgumentException('ui_admin_dataview_framework_plan_invalid');
        }
        $entryIds = is_array($plan['entry_ids'] ?? null) ? $plan['entry_ids'] : [];
        foreach (array_keys(AdminCollectionLayoutPlan::frameworkUtilityClasses()) as $utilityId) {
            if (!in_array($utilityId, $entryIds, true)) {
                throw new InvalidArgumentException('ui_admin_dataview_framework_utility_missing:' . $utilityId);
            }
        }

        return [
            'compatibility_id' => $plan['compatibility_id'] ?? null,
            'profile' => $plan['profile'] ?? null,
            'registry_sha256' => $plan['registry_sha256'] ?? null,
            'plan_sha256' => $plan['plan_sha256'] ?? null,
            'adapter_id' => $plan['adapter']['id'] ?? null,
            'recipe' => $plan['adapter']['upstream_recipe'] ?? null,
            'entry_ids' => $plan['entry_ids'] ?? [],
            'kinds' => $plan['kinds'] ?? [],
            'effects_allowed' => false,
            'uses_framework_utilities_for_layout' => true,
            'layout_utility_classes' => AdminCollectionLayoutPlan::frameworkUtilityClasses(),
            'layout_utility_regions' => AdminCollectionLayoutPlan::frameworkUtilityRegions(),
            'upstream_gap' => $plan['adapter']['support']['upstream_gap'] ?? null,
            'fallback' => $plan['adapter']['support']['fallback'] ?? null,
        ];
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
