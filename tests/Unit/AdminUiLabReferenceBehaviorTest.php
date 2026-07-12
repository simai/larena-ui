<?php

declare(strict_types=1);

$script = (string) file_get_contents(__DIR__ . '/../../resources/js/admin-ui-lab.js');

assert(str_contains($script, '[data-larena-smart-reference-form]'));
assert(str_contains($script, "document.addEventListener('change'"));
assert(str_contains($script, "document.addEventListener('input'"));
assert(str_contains($script, 'form.requestSubmit()'));
assert(str_contains($script, '700'));
assert(str_contains($script, "window.addEventListener('larena-smart-ready'"));
assert(str_contains($script, '[data-sf-modal-panel]'));
assert(str_contains($script, 'sf-modal[display="inline"]'));
assert(str_contains($script, '.larena-lab-preview'));
assert(str_contains($script, '.larena-smart-reference'));
assert(str_contains($script, '.larena-recipe-artifact'));
assert(str_contains($script, "document.addEventListener('focusin'"));
assert(str_contains($script, "document.addEventListener('pointerdown'"));
assert(str_contains($script, 'event.isTrusted'));
assert(str_contains($script, 'target.blur()'));
assert(str_contains($script, "document.body.setAttribute('tabindex', '-1')"));
assert(str_contains($script, 'document.body.focus({ preventScroll: true })'));
assert(!str_contains($script, 'data-larena-reference-preview'));
assert(!str_contains($script, 'setAttribute(\'type\''));
assert(!str_contains($script, 'setAttribute(\'scheme\''));

echo "AdminUiLabReferenceBehaviorTest passed.\n";
