<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use Closure;
use InvalidArgumentException;

/**
 * Registers Smart components as Document node types for RegisteredDocumentRenderer.
 * The Document gives the structure (node, view, slot children); the host gives the
 * request-bound props of each node. Props are never read from the Document.
 */
final class SmartDocumentNodeTypes
{
    /** @var array<string,true> */
    private array $assetTags = [];

    /**
     * @param list<string> $componentKeys Trusted server registrations only.
     * @param Closure(array<string,mixed>):array<string,mixed> $props Request-bound props for one node.
     * @param array<string,mixed> $assetActivation
     */
    public function __construct(
        private readonly SmartManager $smart,
        private readonly array $componentKeys,
        private readonly Closure $props,
        private readonly array $assetActivation,
    ) {}

    /** @return array<string,array{validate:Closure,render:Closure}> */
    public function registrations(): array
    {
        $types = [];
        foreach ($this->componentKeys as $type) {
            // Unknown components fail here, before any Document is accepted.
            $this->smart->manifest($type);
            $types[$type] = [
                'validate' => function (array $node) use ($type): void { $this->validate($node, $type); },
                'render' => function (array $node, array $slots) use ($type): string { return $this->render($node, $slots, $type); },
            ];
        }
        return $types;
    }

    /**
     * Asset tags of every Smart node rendered so far, in first-seen order.
     *
     * @return list<string>
     */
    public function assetTags(): array
    {
        return array_keys($this->assetTags);
    }

    /** @param array<string,mixed> $node */
    private function validate(array $node, string $type): void
    {
        $slots = $node['slots'] ?? [];
        $presentation = $node['presentation'] ?? [];
        if (($node['type'] ?? null) !== $type || !is_string($node['id'] ?? null) || $node['id'] === ''
            || ($node['data'] ?? []) !== [] || ($node['props'] ?? []) !== []
            || !is_array($slots) || array_diff(array_keys($slots), $this->smart->manifest($type)->slotKeys) !== []
            || !is_array($presentation) || array_diff(array_keys($presentation), ['view']) !== []
            || (isset($presentation['view']) && $presentation['view'] !== 'default')) {
            throw new InvalidArgumentException('ui_document_smart_node_invalid:'.$type);
        }
    }

    /** @param array<string,mixed> $node @param array<string,string> $slots */
    private function render(array $node, array $slots, string $type): string
    {
        $this->validate($node, $type);
        $props = ($this->props)($node);
        $artifact = $this->smart->render($type, $props, $this->assetActivation, $slots);
        if (!$artifact->isRenderable()) {
            throw new InvalidArgumentException('ui_document_smart_node_not_renderable:'.$type);
        }
        foreach ($artifact->assetTags() as $tag) {
            $this->assetTags[$tag] = true;
        }
        return $artifact->html();
    }
}
