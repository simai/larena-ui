<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use Closure;
use InvalidArgumentException;
use Larena\Ui\Frontend\SourceBackedComponentRegistry;
use Larena\Ui\Smart;

/**
 * Registers pinned Framework elements (sf-*) as Document node types for RegisteredDocumentRenderer.
 * The Document gives the structure: which element, its children and the named slot each child fills.
 * The host gives the request-bound part of each node: the element's Framework attributes (`props`) and
 * the page's own attributes (`host`, see Smart::render). Neither is read from the Document.
 *
 * A child placed in a named slot carries that slot's name; a child of `default` carries none. The
 * `host.slot-placeholder` node holds a named slot empty, so the element keeps nothing of its own there.
 */
final class SfElementDocumentNodeTypes
{
    public const SLOT_PLACEHOLDER = 'host.slot-placeholder';

    /** @var array<string,string> Node id => the named slot it fills, learned while the Document is checked. */
    private array $slotOf = [];

    /**
     * @param list<string> $tags Trusted server registrations only.
     * @param Closure(array<string,mixed>):array{props?:array<string,mixed>,host?:array<string,string>} $bindings
     */
    public function __construct(
        private readonly array $tags,
        private readonly Closure $bindings,
        private readonly ?SourceBackedComponentRegistry $registry = null,
    ) {}

    /** @return array<string,array{validate:Closure,render:Closure}> */
    public function registrations(): array
    {
        $registry = $this->registry ?? SourceBackedComponentRegistry::bundled();
        $types = [];
        foreach ($this->tags as $tag) {
            // Unknown elements fail here, before any Document is accepted.
            $registry->get($tag);
            $types[$tag] = [
                'validate' => function (array $node) use ($tag): void { $this->validate($node, $tag); },
                'render' => function (array $node, array $slots) use ($tag): string { return $this->render($node, $slots, $tag); },
            ];
        }
        $types[self::SLOT_PLACEHOLDER] = [
            'validate' => function (array $node): void { $this->validate($node, self::SLOT_PLACEHOLDER); },
            'render' => function (array $node, array $slots): string {
                $this->validate($node, self::SLOT_PLACEHOLDER);
                $slot = $this->slotOf[$node['id']] ?? null;
                if ($slot === null || $slots !== []) {
                    throw new InvalidArgumentException('ui_document_slot_placeholder_invalid');
                }

                return '<span slot="'.htmlspecialchars($slot, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" hidden></span>';
            },
        ];

        return $types;
    }

    /** @param array<string,mixed> $node */
    private function validate(array $node, string $type): void
    {
        $slots = $node['slots'] ?? [];
        $presentation = $node['presentation'] ?? [];
        if (($node['type'] ?? null) !== $type || !is_string($node['id'] ?? null) || $node['id'] === ''
            || ($node['data'] ?? []) !== [] || ($node['props'] ?? []) !== []
            || !is_array($slots) || ($type === self::SLOT_PLACEHOLDER && $slots !== [])
            || !is_array($presentation) || array_diff(array_keys($presentation), ['view']) !== []
            || (isset($presentation['view']) && $presentation['view'] !== 'default')) {
            throw new InvalidArgumentException('ui_document_element_node_invalid:'.$type);
        }
        foreach ($slots as $name => $children) {
            if (!is_string($name) || preg_match('/^[a-z][a-z0-9-]{0,39}$/D', $name) !== 1 || !is_array($children)) {
                throw new InvalidArgumentException('ui_document_element_slot_invalid:'.$type);
            }
            foreach ($children as $child) {
                if (is_array($child) && is_string($child['id'] ?? null) && $name !== 'default') {
                    $this->slotOf[$child['id']] = $name;
                }
            }
        }
    }

    /** @param array<string,mixed> $node @param array<string,string> $slots */
    private function render(array $node, array $slots, string $tag): string
    {
        $this->validate($node, $tag);
        $binding = ($this->bindings)($node);
        $props = $binding['props'] ?? [];
        $host = $binding['host'] ?? [];
        if (isset($host['slot'])) {
            throw new InvalidArgumentException('ui_document_element_binding_invalid:'.$tag);
        }
        if (isset($this->slotOf[$node['id']])) {
            $host = ['slot' => $this->slotOf[$node['id']]] + $host;
        }
        $result = Smart::render($tag, $props, $this->registry, $host, implode('', $slots));
        if (!$result->isSafe()) {
            throw new InvalidArgumentException('ui_document_element_not_renderable:'.$tag);
        }

        return $result->html;
    }
}
