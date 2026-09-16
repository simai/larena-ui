<?php

declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Larena\Ui\Runtime\RegisteredDocumentRenderer;

$calls = 0;
$validated = 0;
$types = [
    'layout.page' => [
        'validate' => static function (array $node): void {
            if (array_keys($node['slots'] ?? []) !== ['default']) throw new InvalidArgumentException('slot');
        },
        'render' => static function (array $node, array $slots) use (&$calls): string { ++$calls; return '<main>'.$slots['default'].'</main>'; },
    ],
    'test.text' => [
        'validate' => static function (array $node): void {
            if (!is_string($node['props']['text'] ?? null) || ($node['slots'] ?? []) !== []) throw new InvalidArgumentException('props');
        },
        'render' => static function (array $node, array $slots) use (&$calls): string { ++$calls; return htmlspecialchars($node['props']['text'], ENT_QUOTES, 'UTF-8'); },
    ],
];
$renderer = new RegisteredDocumentRenderer(static function (array $document) use (&$validated): void { ++$validated; }, $types);
$leaf = ['id' => 'a', 'type' => 'test.text', 'props' => ['text' => '<script>literal</script>']];
$doc = ['schema' => 'simai.composition.document.v1', 'root' => ['id' => 'page', 'type' => 'layout.page', 'slots' => ['default' => [$leaf]]]];
assert($renderer->render($doc) === '<main>&lt;script&gt;literal&lt;/script&gt;</main>');
assert($calls === 2 && $validated === 1);
foreach ([
    [...$leaf, 'id' => 'b', 'type' => 'unknown'],
    $leaf,
    [...$leaf, 'id' => 'b', 'props' => ['text' => []]],
] as $bad) {
    $broken = $doc;
    $broken['root']['slots']['default'][] = $bad;
    $before = $calls;
    try { $renderer->render($broken); throw new RuntimeException('Invalid document accepted'); }
    catch (InvalidArgumentException) {}
    assert($calls === $before, 'Renderer ran before complete preflight');
}
$refused = new RegisteredDocumentRenderer(static function (array $document): void { throw new InvalidArgumentException('conformance'); }, $types);
$before = $calls;
try { $refused->render($doc); throw new RuntimeException('Conformance refusal bypassed'); }
catch (InvalidArgumentException) {}
assert($calls === $before);
echo "RegisteredDocumentRendererTest passed: upstream validator required, full preflight, duplicate/type rejection, registered rendering.\n";
