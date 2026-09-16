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
     * @param Closure(string):DataviewDatasetSnapshot $dataset Resolve with server-owned actor, scope and query.
     * @param array<string,array<string,list<array{key:string,label:string}>>> $columns Source => preset => columns.
     * @param array<string,mixed> $assetActivation
     */
    public function __construct(
        private RegisteredListRenderer $lists,
        private Closure $dataset,
        private array $columns,
        private array $assetActivation,
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
        $presentation = $node['presentation'] ?? [];
        if (!is_array($presentation) || array_diff(array_keys($presentation), ['view']) !== []
            || (isset($presentation['view']) && $presentation['view'] !== 'default')) {
            throw new InvalidArgumentException('ui_document_list_presentation_invalid');
        }
    }

    /** @param array<string,mixed> $node @param array<string,string> $slots */
    private function render(array $node, array $slots): string
    {
        $this->validate($node);
        $props = $node['props'];
        $page = ($this->dataset)($props['source_key']);
        $artifact = $this->lists->render($page, $node['id'], $props['title'],
            $this->columns[$props['source_key']][$props['column_preset']], $this->assetActivation);
        if (!$artifact->isRenderable()) throw new InvalidArgumentException('ui_document_list_not_renderable');
        return $artifact->html();
    }
}
