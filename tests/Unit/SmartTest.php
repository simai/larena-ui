<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\Smart;

$button = Smart::render('sf-button', ['text' => 'Create', 'scheme' => 'primary', 'disabled' => false]);
assert(str_contains($button->html, '<sf-button'));
assert(str_contains($button->html, 'text="Create"'));
assert(!str_contains($button->html, '<button'));
assert($button->isSafe());

$table = Smart::render('sf-table', ['aria-label' => 'Pages', 'data' => ['columns' => [['key' => 'title', 'label' => 'Title']], 'rows' => [['title' => 'Test']]]]);
assert(str_contains($table->html, '<sf-table'));
assert(str_contains($table->html, 'type="application/json"'));
assert(str_contains($table->html, 'larena-smart-hydration'));
assert(count($table->assetRequirements) === 6);
assert($table->isSafe());

foreach ([
    'sf-badge' => ['text' => 'Published', 'scheme' => 'success'],
    'sf-alert' => ['type' => 'danger', 'title' => 'Error'],
    'sf-pagination' => ['current' => 2, 'total' => 4],
    'sf-input' => ['name' => 'title', 'label' => 'Title', 'required' => true],
    'sf-checkbox' => ['name' => 'is_active', 'label' => 'Active', 'checked' => true, 'value' => '1'],
    'sf-modal' => ['id' => 'dialog', 'title' => 'Dialog', 'overlay' => true],
    'sf-textarea' => ['name' => 'body', 'label' => 'Body', 'rows' => 8, 'required' => true],
] as $tag => $props) {
    $render = Smart::render($tag, $props);
    assert(str_contains($render->html, '<' . $tag));
    assert($render->isSafe());
}

$dropdown = Smart::render('sf-dropdown', [
    'name' => 'locale',
    'label' => 'Language',
    'value' => 'en',
    'required' => true,
    'options' => [
        ['text' => 'English', 'value' => 'en', 'selected' => true],
        ['text' => 'Русский', 'value' => 'ru'],
    ],
]);
assert(str_contains($dropdown->html, '<sf-dropdown'));
assert(str_contains($dropdown->html, '<sf-list-item text="English" value="en" selected>'));
assert(!str_contains($dropdown->html, 'options='));
assert($dropdown->isSafe());

echo "SmartTest passed\n";
