<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Larena\Ui\Components\AdminComponentCatalog;
use Larena\Ui\Runtime\AdminUiLabRenderer;

$activation = ['activation_owner'=>'larena/core:core.assets','physical_publication_ready'=>true,'writes_database'=>false,'copies_to_root'=>false,'uses_hardcoded_cdn'=>false,'renderable_tags'=>['<link href="/ui.css" rel="stylesheet">']];
$catalog = new AdminComponentCatalog();
assert(array_keys($catalog->definitions()) === ['button','badge','toolbar','empty_state','pagination','field','notice','modal']);
$renderer = new AdminUiLabRenderer($catalog);
foreach (array_keys($catalog->definitions()) as $key) {
    $artifact = $renderer->component($key, ['label'=>'Example','title'=>'Example','message'=>'Example','error'=>'Required'], $activation);
    assert($artifact->isRenderable());
    assert(str_contains($artifact->html(), 'data-larena-smart-component="admin.'));
}
foreach (['dataview','crud_form','dashboard','media_picker','settings_form'] as $key) {
    $artifact = $renderer->recipe($key, [], $activation);
    assert($artifact->isRenderable());
    assert(($artifact->diagnostics['production_ready'] ?? true) === false);
    assert($renderer->recipeRegions($key) !== []);
}
assert(str_contains($renderer->component('modal', [], $activation)->html(), '<dialog'));
assert(str_contains($renderer->component('field', ['error'=>'Required'], $activation)->html(), 'aria-invalid="true"'));
echo "AdminUiLabRendererTest: OK\n";
