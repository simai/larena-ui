<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use Closure;
use InvalidArgumentException;
use Larena\Dataview\Contracts\DataviewDatasetSnapshot;

/** Binds a static registered Document host to authorized request-local owner data. */
final readonly class RegisteredListDocumentNodeRenderer
{
    /**
     * @param Closure(string):array<string,mixed>|null $requestState Trusted request-only profiles and endpoint; never persisted in Document.
     * @param Closure(string):DataviewDatasetSnapshot $dataset Resolve with server-owned actor, scope and query.
     * @param array<string,array<string,list<array{key:string,label:string}>>> $columns Source => preset => columns.
     * @param array<string,mixed> $assetActivation
     * @param array<string,array<string,array{type:string,operators:list<string>,sortable:bool}>> $queryFields Source query contracts.
     * @param array<string,string> $queryEndpoints Trusted Root routes, never composition JSON.
     */
    public function __construct(
        private RegisteredListRenderer $lists,
        private Closure $dataset,
        private array $columns,
        private array $assetActivation,
        private array $queryEndpoints = [],
        private array $queryFields = [],
        private ?Closure $requestState = null,
    ) {}

    /** @return array{validate:Closure,render:Closure} */
    public function registration(): array
    {
        return ['validate' => $this->validate(...), 'render' => $this->render(...)];
    }

    /** @param array<string,mixed> $node */
    private function validate(array $node): void
    {
        $props = $node['props'] ?? null;
        if (($node['type'] ?? null) !== 'larena.registered-list' || !is_array($props)
            || count($props) !== 3 || !is_string($props['source_key'] ?? null)
            || !is_string($props['column_preset'] ?? null) || !is_string($props['title'] ?? null)
            || $props['title'] === '' || !mb_check_encoding($props['title'], 'UTF-8')
            || strlen(mb_convert_encoding($props['title'], 'UTF-16BE', 'UTF-8')) > 1000
            || !isset($this->columns[$props['source_key']][$props['column_preset']])
            || ($node['data'] ?? []) !== [] || ($node['slots'] ?? []) !== []
            || !is_string($node['id'] ?? null) || preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,79}$/D', $node['id']) !== 1) {
            throw new InvalidArgumentException('ui_document_list_registration_invalid');
        }
        if ($this->queryEndpoints !== []) {
            $endpoint = $this->queryEndpoints[$props['source_key']] ?? null;
            if (!is_string($endpoint) || preg_match('#^/[A-Za-z0-9._~/-]{1,300}$#D', $endpoint) !== 1
                || str_starts_with($endpoint, '//') || preg_match('~/\.{1,2}(?:/|$)~', $endpoint) === 1) {
                throw new InvalidArgumentException('ui_document_list_query_route_invalid');
            }
        }
        $this->lists->registeredFilterFields($this->columns[$props['source_key']][$props['column_preset']], $this->queryFields[$props['source_key']] ?? []);
        $presentation = $node['presentation'] ?? [];
        if (!is_array($presentation) || array_diff(array_keys($presentation), ['view']) !== []
            || (isset($presentation['view']) && $presentation['view'] !== 'default')) {
            throw new InvalidArgumentException('ui_document_list_presentation_invalid');
        }
    }

    /** @return array<string,mixed> */
    private function validateRequestState(mixed $requestState): array
    {
        if (!is_array($requestState) || array_diff(array_keys($requestState), ['profiles', 'capabilities', 'user_endpoint', 'record_interaction']) !== []) {
            throw new InvalidArgumentException('ui_document_list_request_state_invalid');
        }
        if (isset($requestState['record_interaction'])) {
            $interaction = $requestState['record_interaction'];
            if (!is_array($interaction) || array_keys($interaction) !== ['kind', 'operation_base', 'fields']
                || $interaction['kind'] !== 'storage-record-v1' || !is_string($interaction['operation_base'])
                || preg_match('#^/admin/composition-lists/[a-z][a-z0-9_.-]{1,119}/records$#D', $interaction['operation_base']) !== 1
                || !is_array($interaction['fields']) || !array_is_list($interaction['fields'])
                || count($interaction['fields']) < 1 || count($interaction['fields']) > 64
                || count(array_unique($interaction['fields'], SORT_REGULAR)) !== count($interaction['fields'])) {
                throw new InvalidArgumentException('ui_document_list_record_interaction_invalid');
            }
            foreach ($interaction['fields'] as $key) if (!is_string($key) || preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,39}$/D', $key) !== 1) {
                throw new InvalidArgumentException('ui_document_list_record_interaction_invalid');
            }
        }
        if (isset($requestState['user_endpoint']) && (!is_string($requestState['user_endpoint'])
            || preg_match('#^/[A-Za-z0-9._~/-]{1,300}$#D', $requestState['user_endpoint']) !== 1
            || str_starts_with($requestState['user_endpoint'], '//')
            || preg_match('~/\.{1,2}(?:/|$)~', $requestState['user_endpoint']) === 1)) {
            throw new InvalidArgumentException('ui_document_list_preferences_route_invalid');
        }
        return $requestState;
    }

    /** @param array<string,mixed> $node @param array<string,string> $slots */
    private function render(array $node, array $slots): string
    {
        $this->validate($node);
        $props = $node['props'];
        $requestState = $this->requestState === null ? [] : ($this->requestState)($props['source_key']);
        $requestState = $this->validateRequestState($requestState);
        if (isset($requestState['record_interaction']) && $requestState['record_interaction']['fields']
            !== array_column($this->columns[$props['source_key']][$props['column_preset']], 'key')) {
            throw new InvalidArgumentException('ui_document_list_record_fields_invalid');
        }
        $page = ($this->dataset)($props['source_key']);
        $artifact = $this->lists->render($page, $node['id'], $props['title'],
            $this->columns[$props['source_key']][$props['column_preset']], $this->assetActivation, $this->queryFields[$props['source_key']] ?? [], isset($requestState['user_endpoint']), isset($requestState['record_interaction']));
        if (!$artifact->isRenderable()) throw new InvalidArgumentException('ui_document_list_not_renderable');
        if ($this->queryEndpoints === []) return $artifact->html();
        $filters = [];
        foreach ($page->query->normalizedFilters() as $filter) $filters[$filter['field']] = ['operator' => $filter['operator'], 'value' => $filter['value']];
        $state = ['query_endpoint' => $this->queryEndpoints[$props['source_key']], 'query' => [
            'search' => $page->query->search ?? '', 'filters' => (object) $filters, 'sort' => $page->query->normalizedSort(),
            'page' => $page->pagination->page, 'page_size' => $page->pagination->perPage]] + $requestState;
        $json = json_encode($state, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $escape = static fn (string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<section data-larena-dataview-workbench id="'.$escape($node['id']).'-host"><h2>'.$escape($props['title']).'</h2>'
            .$artifact->html().(isset($requestState['user_endpoint']) ? $this->lists->preferenceControls($node['id'], $this->assetActivation) : '').(isset($requestState['record_interaction']) ? $this->lists->recordControls($node['id'], $this->columns[$props['source_key']][$props['column_preset']], $this->assetActivation) : '')
            .'<p data-larena-dataview-status role="status" aria-live="polite"></p>'
            .'<script type="application/json" data-larena-dataview-state>'.$json.'</script></section>';
    }
}
