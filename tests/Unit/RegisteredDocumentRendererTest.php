<?php

declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Larena\Ui\Runtime\RegisteredDocumentRenderer;

$expectCount = static function (int $actual, int $expected, string $message = 'Unexpected callback count'): void {
    if ($actual !== $expected) throw new RuntimeException($message);
};
$calls = new class {
    private int $value = 0;
    public function increment(): void { ++$this->value; }
    public function count(): int { return $this->value; }
};
$validated = 0;
$types = [
    'layout.page' => [
        'validate' => static function (array $node): void {
            if (array_keys($node['slots'] ?? []) !== ['default']) throw new InvalidArgumentException('slot');
        },
        'render' => static function (array $node, array $slots) use ($calls): string { $calls->increment(); return '<main>'.$slots['default'].'</main>'; },
    ],
    'test.text' => [
        'validate' => static function (array $node): void {
            if (!is_string($node['props']['text'] ?? null) || ($node['slots'] ?? []) !== []) throw new InvalidArgumentException('props');
        },
        'render' => static function (array $node, array $slots) use ($calls): string { $calls->increment(); return htmlspecialchars($node['props']['text'], ENT_QUOTES, 'UTF-8'); },
    ],
];
$renderer = new RegisteredDocumentRenderer(static function (array $document) use (&$validated): void { ++$validated; }, $types);
$leaf = ['id' => 'a', 'type' => 'test.text', 'props' => ['text' => '<script>literal</script>']];
$doc = ['schema' => 'simai.composition.document.v1', 'root' => ['id' => 'page', 'type' => 'layout.page', 'slots' => ['default' => [$leaf]]]];
assert($renderer->render($doc) === '<main>&lt;script&gt;literal&lt;/script&gt;</main>');
$expectCount($calls->count(), 2);
assert($validated === 1);
foreach ([
    [...$leaf, 'id' => 'b', 'type' => 'unknown'],
    $leaf,
    [...$leaf, 'id' => 'b', 'props' => ['text' => []]],
] as $bad) {
    $broken = $doc;
    $broken['root']['slots']['default'][] = $bad;
    $before = $calls->count();
    try { $renderer->render($broken); throw new RuntimeException('Invalid document accepted'); }
    catch (InvalidArgumentException) {}
    $expectCount($calls->count(), $before, 'Renderer ran before complete preflight');
}
$refused = new RegisteredDocumentRenderer(static function (array $document): void { throw new InvalidArgumentException('conformance'); }, $types);
$before = $calls->count();
try { $refused->render($doc); throw new RuntimeException('Conformance refusal bypassed'); }
catch (InvalidArgumentException) {}
$expectCount($calls->count(), $before);
echo "RegisteredDocumentRendererTest passed: upstream validator required, full preflight, duplicate/type rejection, registered rendering.\n";
