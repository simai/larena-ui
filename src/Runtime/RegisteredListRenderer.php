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
     * @param array<string,array{type:string,operators:list<string>,sortable:bool}> $queryFields Server-owned domain query contract.
     * @param array<string,mixed> $assetActivation
     */
    public function render(DataviewDatasetSnapshot $page, string $instanceId, string $title, array $columns, array $assetActivation, array $queryFields = [], bool $personalSettings = false, bool $recordActions = false): FrontendRenderArtifact
    {
        if (!$page->isValid() || preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,79}$/D', $instanceId) !== 1) throw new InvalidArgumentException('ui_registered_list_invalid');
        $filterFields = $this->registeredFilterFields($columns, $queryFields);
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
        $data = ['columns' => $columns, 'rows' => $rows, 'filterFields' => $filterFields,
            'pagination' => ['page' => $page->pagination->page, 'pageSize' => $page->pagination->perPage, 'total' => $page->pagination->total]];
        return $this->smart->renderView('dataview.table', 'default', ['title' => $title], $assetActivation,
            slots: ['content' => $this->noScriptTable($rows, $columns, $title)],
            childProps: ['toolbar' => $this->toolbarInstanceProps($instanceId, $columns, $queryFields, $page->pagination->perPage), 'grid' => ['id' => $instanceId.'-table', 'aria-label' => $title,
                'selectable' => false, 'settings' => $personalSettings, 'actions' => $recordActions],
                'pagination' => ['id' => $instanceId.'-pagination', 'current' => $page->pagination->page, 'total' => $page->pagination->total, 'page-size' => $page->pagination->perPage, 'page-sizes' => '10,20,50,100']],
            requestDataBindings: ['grid' => ['data' => $data]]);
    }

    /** Every registered toolbar child belongs to this list, including nested query controls. */
    private function toolbarInstanceProps(string $instanceId, array $columns, array $queryFields, int $pageSize): array
    {
        $prefix = 'list-'.substr(hash('sha256', $instanceId), 0, 20);
        $children = [];
        foreach (['saved_view', 'search', 'submit', 'create'] as $child) {
            $children[$child] = ['id' => $prefix.'-'.$child];
        }
        $options = [];
        foreach (['filter_field', 'filter_value', 'sort_field', 'sort_direction', 'per_page'] as $child) {
            $options[$child] = ['id' => $prefix.'-'.$child];
        }
        $filterOptions = []; $sortOptions = [];
        foreach ($columns as $column) {
            $field = $queryFields[$column['key']] ?? null;
            if ($field === null) continue;
            $this->validateQueryField($field);
            $option = ['text' => $column['label'], 'value' => $column['key'], 'type' => 'text', 'size' => '1',
                'selected' => false, 'disabled' => false, 'aria-label' => $column['label']];
            if ($field['type'] === 'string' && in_array('eq', $field['operators'], true)) $filterOptions[] = $option;
            if (($field['sortable'] ?? false) === true) $sortOptions[] = $option;
        }
        foreach (['filter_field' => $filterOptions, 'sort_field' => $sortOptions] as $child => $registeredOptions) {
            if ($registeredOptions !== []) $registeredOptions[0]['selected'] = true;
            $options[$child] += ['value' => $registeredOptions[0]['value'] ?? '__unavailable', 'options' => $registeredOptions,
                'disabled' => $registeredOptions === [], 'required' => false];
        }
        $options['filter_value']['disabled'] = $filterOptions === [];
        $options['per_page']['value'] = (string) $pageSize;
        $children['query_options'] = ['_children' => $options];
        return ['_children' => $children];
    }

    /** A registered control for the existing request-local preferences endpoint. */
    public function preferenceControls(string $instanceId, array $activation): string
    {
        $id = 'preferences-'.substr(hash('sha256', $instanceId), 0, 20).'-reset';
        $text = 'Сбросить личные настройки';
        $artifact = $this->smart->render('ui.button', ['id' => $id, 'text' => $text,
            'size' => '1', 'type' => 'outline', 'scheme' => 'primary', 'loading' => false,
            'disabled' => false, 'native-type' => 'button', 'aria-label' => $text], $activation);
        if (!$artifact->isRenderable()) throw new InvalidArgumentException('ui_preferences_controls_unavailable');
        return '<span data-larena-dataview-reset>'.$artifact->html().'</span>';
    }

    /** Static registered controls; owner values and credentials never enter this structure. */
    public function recordControls(string $instanceId, array $columns, array $activation): string
    {
        $this->registeredFilterFields($columns);
        foreach ($columns as $column) {
            if (preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,39}$/D', $column['key']) !== 1) {
                throw new InvalidArgumentException('ui_record_control_field_invalid');
            }
        }
        $instanceId = 'record-'.substr(hash('sha256', $instanceId), 0, 20);
        $fields = '';
        foreach ($columns as $column) {
            $artifact = $this->smart->render('ui.input', ['id' => $instanceId.'-field-'.$column['key'],
                'name' => $column['key'], 'label' => $column['label'], 'value' => '', 'required' => true,
                'type' => 'bordered', 'size' => '1', 'disabled' => false, 'error' => false,
                'hint' => $column['label']], $activation);
            if (!$artifact->isRenderable()) throw new InvalidArgumentException('ui_record_controls_unavailable');
            $fields .= $artifact->html();
        }
        $button = function (string $key, string $text) use ($instanceId, $activation): string {
            $artifact = $this->smart->render('ui.button', ['id' => $instanceId.'-'.$key, 'text' => $text,
                'size' => '1', 'type' => 'outline', 'scheme' => 'primary', 'loading' => false,
                'disabled' => false, 'native-type' => 'button', 'aria-label' => $text], $activation);
            if (!$artifact->isRenderable()) throw new InvalidArgumentException('ui_record_controls_unavailable');
            return '<span data-larena-record-control="'.$key.'">'.$artifact->html().'</span>';
        };
        $editor = $this->smart->renderView('admin.record_editor', 'default',
            ['title' => 'Запись', 'mode' => 'update', 'expanded' => true], $activation,
            ['fields' => $fields, 'actions' => $button('record-save', 'Подтвердить').$button('record-cancel', 'Отмена')]);
        if (!$editor->isRenderable()) throw new InvalidArgumentException('ui_record_controls_unavailable');
        return $button('record-create', 'Создать запись').$button('record-restore', 'Восстановить последнюю архивированную запись')
            .'<div data-larena-record-panel hidden><form data-larena-record-form>'.$editor->html().'</form><p data-larena-record-feedback role="status" aria-live="polite"></p></div>';
    }
    /**
     * Pure preflight and Smart projection; never reads domain data.
     * @param array<array-key,mixed> $columns Server-owned presentation registration.
     * @param array<string,array{type:string,operators:list<string>,sortable:bool}> $queryFields
     * @return list<array<string,mixed>>
     */
    public function registeredFilterFields(array $columns, array $queryFields = []): array
    {
        if ($columns === [] || !array_is_list($columns) || count($columns) > 64) {
            throw new InvalidArgumentException('ui_registered_list_invalid');
        }
        $keys = [];
        foreach ($columns as $column) {
            if (!is_array($column) || array_keys($column) !== ['key', 'label'] || !is_string($column['key']) || !is_string($column['label'])
                || $column['key'] === 'id' || isset($keys[$column['key']])) throw new InvalidArgumentException('ui_registered_list_column_invalid');
            $keys[$column['key']] = true;
        }
        $filterFields = [];
        foreach ($columns as $column) {
            $field = $queryFields[$column['key']] ?? null;
            if ($field === null) continue;
            $this->validateQueryField($field);
            // Project only controls supported by both the domain contract and this adapter.
            if ($field['type'] !== 'string') continue;
            $operators = array_values(array_intersect(['eq', 'contains'], $field['operators']));
            if ($operators === []) continue;
            $filterFields[] = ['key' => $column['key'], 'label' => $column['label'], 'filter' => [
                'enabled' => true, 'control' => 'text', 'operators' => $operators,
                'defaultOperator' => $operators[0], 'options' => []]];
        }
        return $filterFields;
    }

    /** Validate an external registration at the presentation boundary, including malformed callers. */
    private function validateQueryField(mixed $field): void
    {
        if (!is_array($field) || !is_string($field['type'] ?? null) || !is_array($field['operators'] ?? null)
            || !array_is_list($field['operators']) || count(array_filter($field['operators'], 'is_string')) !== count($field['operators'])) {
            throw new InvalidArgumentException('ui_registered_list_query_field_invalid');
        }
    }

    /** @param list<array<string,mixed>> $rows @param array<array-key,mixed> $columns */
    private function noScriptTable(array $rows, array $columns, string $title): string
    {
        $escape = static fn (string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<noscript><table data-larena-list-ssr="true"><caption>'.$escape($title).'</caption><thead><tr>';
        foreach ($columns as $column) $html .= '<th scope="col">'.$escape($column['label']).'</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columns as $column) {
                $value = $row[$column['key']];
                $text = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
                $html .= '<td>'.$escape($text).'</td>';
            }
            $html .= '</tr>';
        }
        return $html.'</tbody></table></noscript>';
    }

}
