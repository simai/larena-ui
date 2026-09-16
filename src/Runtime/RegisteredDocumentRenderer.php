<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use Closure;
use InvalidArgumentException;

/** PHP rendering adapter for an already normalized Framework Document, not a compiler. */
final readonly class RegisteredDocumentRenderer
{
    /**
     * @param Closure(array<string,mixed>):void $validateDocument Exact pinned Framework conformance validator.
     * @param array<string,array{validate:Closure,render:Closure}> $types Trusted server registrations only.
     */
    public function __construct(private Closure $validateDocument, private array $types) {}

    /** @param array<string,mixed> $document */
    public function render(array $document): string
    {
        ($this->validateDocument)($document);
        if (($document['schema'] ?? null) !== 'simai.composition.document.v1' || !is_array($document['root'] ?? null)) {
            throw new InvalidArgumentException('ui_document_invalid');
        }
        $seen = [];
        $count = 0;
        // Complete preflight before any renderer is invoked or request-bound data is resolved.
        $this->inspect($document['root'], $seen, $count, 1);
        return $this->nodeHtml($document['root']);
    }

    /** @param array<string,mixed> $node @param array<string,bool> $seen */
    private function inspect(array $node, array &$seen, int &$count, int $depth): void
    {
        if ($depth > 32 || ++$count > 2000) throw new InvalidArgumentException('ui_document_limit');
        $id = $node['id'] ?? null;
        $type = $node['type'] ?? null;
        if (!is_string($id) || $id === '' || isset($seen[$id]) || !is_string($type) || !isset($this->types[$type])) {
            throw new InvalidArgumentException('ui_document_node_unregistered_or_duplicate');
        }
        $seen[$id] = true;
        $registration = $this->types[$type];
        // Validators are pure manifest checks. Owner reads belong to render callbacks after preflight.
        ($registration['validate'])($node);
        $slots = $node['slots'] ?? [];
        if (!is_array($slots)) throw new InvalidArgumentException('ui_document_slots_invalid');
        foreach ($slots as $name => $children) {
            if (!is_string($name) || !is_array($children) || !array_is_list($children)) throw new InvalidArgumentException('ui_document_slot_invalid');
            foreach ($children as $child) {
                if (!is_array($child)) throw new InvalidArgumentException('ui_document_child_invalid');
                $this->inspect($child, $seen, $count, $depth + 1);
            }
        }
    }

    /** @param array<string,mixed> $node */
    private function nodeHtml(array $node): string
    {
        $slots = [];
        foreach (($node['slots'] ?? []) as $name => $children) {
            $html = '';
            foreach ($children as $child) $html .= $this->nodeHtml($child);
            $slots[$name] = $html;
        }
        $html = ($this->types[$node['type']]['render'])($node, $slots);
        if (!is_string($html)) throw new InvalidArgumentException('ui_document_renderer_result_invalid');
        return $html;
    }
}
