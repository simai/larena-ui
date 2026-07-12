<?php

declare(strict_types=1);

$script = (string) file_get_contents(__DIR__ . '/../../resources/js/admin-ui-lab.js');
$css = (string) file_get_contents(__DIR__ . '/../../resources/css/admin-ui-lab.css');

assert(str_contains($script, '[data-larena-catalog-search-panel]'));
assert(str_contains($script, '[data-larena-catalog-card]'));
assert(str_contains($script, "input.addEventListener('input', update)"));
assert(str_contains($script, "input.addEventListener('search', update)"));
assert(str_contains($script, "event.key !== 'Escape'"));
assert(str_contains($script, 'card.hidden = !matches'));
assert(str_contains($script, 'grid.hidden = visible === 0'));
assert(str_contains($script, "clear.hidden = query === ''"));
assert(str_contains($script, 'input.focus()'));
assert(str_contains($css, '.larena-catalog-search'));
assert(str_contains($css, 'grid-template-rows:8rem'));
assert(str_contains($css, '.larena-lab-card[hidden]{display:none}'));
assert(str_contains($css, 'pointer-events:none'));
assert(str_contains($css, '-webkit-line-clamp:2'));
assert(str_contains($css, 'var(--sf-on-surface-variant'));
assert(str_contains($css, '@media(max-width:390px)'));

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
