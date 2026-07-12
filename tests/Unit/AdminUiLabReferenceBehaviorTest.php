<?php

declare(strict_types=1);

$script = (string) file_get_contents(__DIR__ . '/../../resources/js/admin-ui-lab.js');

assert(str_contains($script, '[data-larena-smart-reference-form]'));
assert(str_contains($script, "document.addEventListener('change'"));
assert(str_contains($script, "document.addEventListener('input'"));
assert(str_contains($script, 'form.requestSubmit()'));
assert(str_contains($script, '700'));
assert(!str_contains($script, 'data-larena-reference-preview'));
assert(!str_contains($script, 'setAttribute(\'type\''));
assert(!str_contains($script, 'setAttribute(\'scheme\''));

echo "AdminUiLabReferenceBehaviorTest passed.\n";
