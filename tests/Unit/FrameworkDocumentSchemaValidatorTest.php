<?php

declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Larena\Ui\Runtime\FrameworkDocumentSchemaValidator;
use Larena\Ui\Runtime\RegisteredDocumentRenderer;

$root = getenv('SIMAI_UI_ROOT');
if (!is_string($root) || $root === '') throw new RuntimeException('Exact SIMAI_UI_ROOT required for Document schema proof');
$path = $root.'/distr/core/contracts/composition-document.v1.schema.json';
$digest = 'sha256:a3eb3e2d2dc6505a9848174da60e0afab812b38b809e45a1fb583d2b6c2e6d80';
$validator = FrameworkDocumentSchemaValidator::fromPinnedSchema($path, $digest);
$json = '{"schema":"simai.composition.document.v1","id":"page.lists","profile":"ui-layout","root":{"id":"page","type":"layout.page","props":{},"data":{},"slots":{"default":[{"id":"list","type":"larena.registered-list","props":{"source_key":"auth.users","column_preset":"users","title":"Пользователи"}}]}}}';
$document = $validator->document($json);
$binding = $validator->binding($json);
$binding($document);
$adapter = new RegisteredDocumentRenderer($binding, [
    'layout.page' => ['validate' => static function (array $node): void {},
        'render' => static fn (array $node, array $slots): string => '<main>'.$slots['default'].'</main>'],
    'larena.registered-list' => ['validate' => static function (array $node): void {},
        'render' => static fn (array $node, array $slots): string => htmlspecialchars($node['props']['title'], ENT_QUOTES, 'UTF-8')],
]);
assert($adapter->render($document) === '<main>Пользователи</main>');
assert($document['root']['props'] === []);
assert($document['root']['data'] === []);
$changes = [
    str_replace('"props":{}', '"props":[]', $json),
    str_replace('"data":{}', '"data":[]', $json),
    str_replace('"profile":"ui-layout"', '"profile":"unknown"', $json),
    str_replace('"id":"page.lists",', '', $json),
    str_replace('"source_key":"auth.users"', '"source_key":"auth.users","password":"not-a-real-secret"', $json),
    str_replace('"root":', '"extra":true,"root":', $json),
    str_replace('"type":"larena.registered-list"', '"type":"larena.registered-list","presentation":{"modifiers":["x","x"]}', $json),
    str_replace('"root":', '"extensions":{"not_namespaced":{}},"root":', $json),
    str_replace('"root":', '"extensions":{"larena:test":{"csrf_token":"synthetic-placeholder"}},"root":', $json),
];
foreach ($changes as $invalid) {
    try { $validator->document($invalid); throw new RuntimeException('Invalid document shape accepted'); }
    catch (InvalidArgumentException) {}
}
$modified = $document;
$modified['root']['slots']['default'][0]['props']['source_key'] = 'storage.probe';
try { $binding($modified); throw new RuntimeException('Mutation after validation accepted'); }
catch (InvalidArgumentException) {}
try { FrameworkDocumentSchemaValidator::fromPinnedSchema($path, 'sha256:'.str_repeat('0',64)); throw new RuntimeException('Wrong schema digest accepted'); }
catch (InvalidArgumentException) {}
try { $validator->document(str_repeat(' ',1048577)); throw new RuntimeException('Byte limit bypassed'); }
catch (InvalidArgumentException) {}
$schema = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
foreach (['futureKeyword' => true, '$ref' => 'https://example.invalid/schema'] as $key => $value) {
    $future = $schema;
    $future[$key] = $value;
    $bytes = json_encode($future, JSON_THROW_ON_ERROR);
    $temporary = tempnam(sys_get_temp_dir(), 'larena-doc-schema-');
    if (!is_string($temporary)) throw new RuntimeException('No isolated schema fixture file');
    try {
        file_put_contents($temporary, $bytes);
        $unsupported = FrameworkDocumentSchemaValidator::fromPinnedSchema($temporary, 'sha256:'.hash('sha256', $bytes));
        try { $unsupported->document($json); throw new RuntimeException('Unsupported owner schema capability silently accepted'); }
        catch (InvalidArgumentException) {}
    } finally { unlink($temporary); }
}
echo "FrameworkDocumentSchemaValidatorTest passed: pinned schema, raw object/array distinction, nine shape refusals, source mutation and digest protection.\n";
