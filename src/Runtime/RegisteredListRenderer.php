<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use InvalidArgumentException;
use Larena\Dataview\Contracts\DataviewDatasetSnapshot;
use Larena\Ui\Contracts\FrontendRenderArtifact;

/** Query data stays domain-owned; presentation uses the registered composite Smart view. */
final readonly class RegisteredListRenderer
{
    public function __construct(private SmartManager $smart) {}

    /**
     * @param array<array-key,mixed> $columns Raw server-owned presentation registration, not request JSON.
     * @param array<string,mixed> $assetActivation
     */
    public function render(DataviewDatasetSnapshot $page, string $instanceId, string $title, array $columns, array $assetActivation): FrontendRenderArtifact
    {
        if (!$page->isValid() || preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,79}$/D', $instanceId) !== 1
            || $columns === [] || !array_is_list($columns) || count($columns) > 64) throw new InvalidArgumentException('ui_registered_list_invalid');
        $keys = [];
        foreach ($columns as $column) {
            if (!is_array($column) || array_keys($column) !== ['key', 'label'] || !is_string($column['key']) || !is_string($column['label'])
                || $column['key'] === 'id' || isset($keys[$column['key']])) throw new InvalidArgumentException('ui_registered_list_column_invalid');
            $keys[$column['key']] = true;
        }
        $rows = [];
        foreach ($page->rows as $row) {
            $id = $row['record_id'] ?? null;
            if (!is_int($id) && !is_string($id)) throw new InvalidArgumentException('ui_registered_list_identity_invalid');
            $display = ['id' => $id];
            foreach ($columns as $column) {
                if (!array_key_exists($column['key'], $row)) throw new InvalidArgumentException('ui_registered_list_field_missing');
                $value = $row[$column['key']];
                if ($value !== null && !is_scalar($value)) throw new InvalidArgumentException('ui_registered_list_cell_invalid');
                $display[$column['key']] = $value ?? '';
            }
            $rows[] = $display;
        }
        $data = ['columns' => $columns, 'rows' => $rows,
            'pagination' => ['page' => $page->pagination->page, 'pageSize' => $page->pagination->perPage, 'total' => $page->pagination->total]];
        return $this->smart->renderView('dataview.table', 'default', ['title' => $title], $assetActivation,
            childProps: ['grid' => ['id' => $instanceId.'-table', 'aria-label' => $title,
                'selectable' => false, 'settings' => false, 'actions' => false],
                'pagination' => ['id' => $instanceId.'-pagination', 'current' => $page->pagination->page, 'total' => $page->pagination->total]],
            requestDataBindings: ['grid' => ['data' => $data]]);
    }
}
