<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Frontend\FrontendRuntimeLock;
use Larena\Ui\Runtime\SmartManager;

$activation = [
    'schema' => 'larena.core_assets.activation_contract.v1',
    'status' => 'ready',
    'activation_owner' => 'larena/core:core.assets',
    'activation_mode' => 'verified_immutable_bundle',
    'physical_publication_ready' => true,
    'writes_database' => false,
    'copies_to_root' => false,
    'uses_hardcoded_cdn' => false,
    'runtime_pair' => FrontendRuntimeLock::bundled()->pairId(),
    'renderable_tags' => ['<script src="/larena/assets/sf/core.js"></script>'],
];

$manager = SmartManager::withDefaults();
$render = static function (array $labels) use ($manager, $activation): string {
    $artifact = $manager->renderView('ui.pagination', 'default', $labels, $activation);
    assert($artifact->isRenderable());

    return $artifact->html();
};

$en = $render([
    'selected-label' => 'Selected:',
    'total-label' => 'Total:',
    'pages-label' => 'Pages:',
    'previous-label' => 'Previous page',
    'page-label' => 'Page',
    'next-label' => 'Next page',
    'last-label' => 'Last',
]);
foreach (['selected-label="Selected:"', 'pages-label="Pages:"', 'last-label="Last"'] as $attribute) {
    assert(str_contains($en, $attribute));
}
foreach (['Отмечено', 'Страницы', 'Последняя'] as $russianLabel) {
    assert(!str_contains($en, $russianLabel));
}

$ru = $render([
    'show-more-text' => 'Показать ещё',
    'selected-label' => 'Отмечено:',
    'total-label' => 'Всего:',
    'pages-label' => 'Страницы:',
    'previous-label' => 'Предыдущая страница',
    'page-label' => 'Страница',
    'next-label' => 'Следующая страница',
    'last-label' => 'Последняя',
    'page-size-label' => 'Элементов на странице',
    'aria-label' => 'Навигация по страницам',
]);
foreach (['selected-label="Отмечено:"', 'pages-label="Страницы:"', 'last-label="Последняя"'] as $attribute) {
    assert(str_contains($ru, $attribute));
}

$runtime = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/source-backed-sf/catalog/pagination/smart/js/pagination.js');
assert(is_string($runtime));
foreach (['selectedLabel', 'showTotalText', 'pagesLabel', 'lastPageText'] as $property) {
    assert(str_contains($runtime, $property));
}
foreach (['Отмечено', 'Страницы', 'Последняя'] as $hardCodedLabel) {
    assert(!str_contains($runtime, $hardCodedLabel));
}

echo "PaginationLocalizationContractTest passed.\n";
