<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Developer\SmartAiCatalogProjection;
use Larena\Ui\Developer\SmartCatalogProjection;
use Larena\Ui\Developer\SmartInvocationExampleBuilder;
use Larena\Ui\Registry\SmartRegistry;

$catalog = new SmartCatalogProjection(SmartRegistry::withDefaults(), new SmartInvocationExampleBuilder());
$ai = new SmartAiCatalogProjection($catalog);

$first = $ai->toArray('en');
$second = $ai->toArray('en');
$firstJson = $ai->toJson('en');
$secondJson = $ai->toJson('en');

assert($first === $second);
assert($firstJson === $secondJson);
assert($first['schema'] === 'larena.ui.smart_ai_catalog.v1');
assert($first['source'] === 'SmartRegistry');
assert($first['locale'] === 'en');
assert(array_column($first['components'], 'key') === ['ui.button']);
assert($first['recipes'] === []);
assert($first['components'][0] === $catalog->components('en')[0]->toArray());
assert($first['components'][0]['readiness'] === [
    'safe_to_suggest' => true,
    'safe_to_render' => true,
    'safe_to_bind_data' => false,
    'safe_to_execute_effect' => false,
]);
assert($first['nonclaims'] === [
    'production_ready' => false,
    'all_packages_ready' => false,
    'catalog_is_canonical_source' => false,
    'data_binding_authorized' => false,
    'effect_execution_authorized' => false,
]);
assert(!str_contains($firstJson, '/Users/'));
assert(!str_contains($firstJson, 'file://'));
assert(!str_contains($firstJson, '../'));
assert(!str_contains($firstJson, 'generated_at'));
assert(!str_contains($firstJson, 'created_at'));
assert(!str_contains($firstJson, 'callback'));
assert(!str_contains($firstJson, '"classes"'));
assert(!str_contains($firstJson, '"templates"'));
assert(!str_contains($firstJson, '"raw_html"'));
assert(str_contains($firstJson, 'resources/smart/ui-button/manifest.json'));
assert(str_contains($firstJson, 'Simai Framework'));
assert(str_contains($firstJson, '<sf-button'));
assert(str_contains($firstJson, 'Smart::render'));

$russian = $ai->toArray('ru');
assert($russian['components'][0]['title'] === 'Кнопка');
assert($russian['locale'] === 'ru');

echo "SmartAiCatalogProjectionTest passed.\n";
