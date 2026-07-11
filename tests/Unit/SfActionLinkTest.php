<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Larena\Ui\SfActionLink;

$link = SfActionLink::render('/admin/pages?locale=ru&draft=1', 'Create <page>', 'primary', 'default', '1', true);

assert(str_contains($link->html, '<a href="/admin/pages?locale=ru&amp;draft=1"'));
assert(str_contains($link->html, 'class="sf-button sf-button--default sf-button--primary sf-button--size-1"'));
assert(str_contains($link->html, 'data-larena-ui-runtime="larena/ui:sf5_action_link"'));
assert(str_contains($link->html, 'aria-current="page"'));
assert(str_contains($link->html, '<span class="sf-button-text-container">Create &lt;page&gt;</span>'));
assert(!str_contains($link->html, '<button'));
assert($link->isSafe());

foreach ([
    ['scheme', static fn () => SfActionLink::render('/', 'Bad', 'unknown')],
    ['type', static fn () => SfActionLink::render('/', 'Bad', 'primary', 'unknown')],
    ['size', static fn () => SfActionLink::render('/', 'Bad', 'primary', 'default', '99')],
] as [$boundary, $render]) {
    try {
        $render();
        throw new RuntimeException('Expected invalid ' . $boundary . ' to fail closed.');
    } catch (InvalidArgumentException $exception) {
        assert(str_contains($exception->getMessage(), 'ui_sf_action_link_invalid_' . $boundary));
    }
}

echo "SfActionLinkTest passed\n";
