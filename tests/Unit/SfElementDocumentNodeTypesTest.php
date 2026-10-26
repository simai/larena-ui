<?php

declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Larena\Ui\Runtime\RegisteredDocumentRenderer;
use Larena\Ui\Runtime\SfElementDocumentNodeTypes;
use Larena\Ui\Smart;

// Framework elements as Document nodes: the Document gives the elements and the slot each child fills,
// the host gives each node's Framework attributes and its own page attributes.
$bindings = [
    'view' => ['props' => ['id' => 'list', 'aria-label' => 'Records', 'size' => '1/2'], 'host' => ['data-larena-composite' => 'x', 'style' => '--a:1']],
    'table' => ['props' => ['id' => 'list-table', 'aria-label' => 'Records', 'selectable' => false]],
    'pages' => ['props' => ['current' => 1, 'total' => 3]],
];
$seen = [];
$types = new SfElementDocumentNodeTypes(['sf-data-view', 'sf-table', 'sf-pagination'], static function (array $node) use ($bindings, &$seen): array {
    $seen[] = $node['id'];
    return $bindings[$node['id']] ?? [];
});
$document = ['schema' => 'simai.composition.document', 'schema_version' => 1, 'root' => ['id' => 'view', 'type' => 'sf-data-view', 'slots' => [
    'toolbar' => [['id' => 'toolbar', 'type' => SfElementDocumentNodeTypes::SLOT_PLACEHOLDER]],
    'content' => [['id' => 'table', 'type' => 'sf-table']],
    'pagination' => [['id' => 'pages', 'type' => 'sf-pagination']],
]]];
$html = (new RegisteredDocumentRenderer(static function (array $doc): void {}, $types->registrations()))->render($document);
// Host attributes first, then the element's own; children carry the slot they fill, in Document order.
assert(str_starts_with($html, '<sf-data-view data-larena-composite="x" style="--a:1" id="list" data-larena-smart-runtime="'), $html);
assert(str_contains($html, '<span slot="toolbar" hidden></span><sf-table slot="content" id="list-table" data-larena-smart-runtime="'));
assert(str_contains($html, 'selectable="false"></sf-table><sf-pagination slot="pagination" data-larena-smart-runtime="'));
assert(str_ends_with($html, 'current="1" total="3"></sf-pagination></sf-data-view>'));
assert($seen === ['table', 'pages', 'view']);

$fails = static function (callable $run, string $prefix): void {
    try {
        $run();
    } catch (InvalidArgumentException $exception) {
        assert(str_starts_with($exception->getMessage(), $prefix), $exception->getMessage());
        return;
    }
    throw new RuntimeException('expected '.$prefix);
};
$render = static fn (array $doc, Closure $bind) => (new RegisteredDocumentRenderer(static function (array $d): void {},
    (new SfElementDocumentNodeTypes(['sf-data-view', 'sf-table'], $bind))->registrations()))->render($doc);
$none = static fn (array $node): array => [];
// Unknown elements, props in the Document, a slot placeholder outside a named slot and markup-like host names fail closed.
$fails(fn () => (new SfElementDocumentNodeTypes(['sf-nothing-here'], $none))->registrations(), 'ui_smart_component_unknown');
$fails(fn () => $render(['schema' => 'simai.composition.document', 'schema_version' => 1, 'root' => ['id' => 'v', 'type' => 'sf-data-view', 'props' => ['id' => 'x']]], $none), 'ui_document_element_node_invalid');
$fails(fn () => $render(['schema' => 'simai.composition.document', 'schema_version' => 1, 'root' => ['id' => 'v', 'type' => 'sf-data-view', 'slots' => [
    'default' => [['id' => 'p', 'type' => SfElementDocumentNodeTypes::SLOT_PLACEHOLDER]]]]], $none), 'ui_document_slot_placeholder_invalid');
$fails(fn () => $render(['schema' => 'simai.composition.document', 'schema_version' => 1, 'root' => ['id' => 'v', 'type' => 'sf-data-view']],
    static fn (array $node): array => ['host' => ['onclick' => 'x']]), 'ui_smart_host_attribute_invalid');
$fails(fn () => $render(['schema' => 'simai.composition.document', 'schema_version' => 1, 'root' => ['id' => 'v', 'type' => 'sf-data-view']],
    static fn (array $node): array => ['host' => ['slot' => 'x']]), 'ui_document_element_binding_invalid');
$fails(fn () => Smart::render('sf-table', ['data-state' => 'ready'], null, ['data-state' => 'x']), 'ui_smart_host_attribute_invalid');
$fails(fn () => Smart::render('sf-table', [], null, ['data-larena-smart-runtime' => 'x']), 'ui_smart_host_attribute_invalid');
// Without host attributes or content an element renders as before.
assert(Smart::render('sf-pagination', ['current' => 1])->html === Smart::render('sf-pagination', ['current' => 1], null, [], '')->html);

echo "SfElementDocumentNodeTypesTest passed.\n";
