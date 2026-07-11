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

echo "SmartTest passed\n";
