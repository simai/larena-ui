import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import test from 'node:test';

// sf-admin-menu's counters (simai/ui-source#12, second part): compact, an
// item's counter sits on its icon or avatar as the Framework's anchored badge;
// expanded, it stands at the row's end; the item's name holds its words and
// the count. tests/browser/admin-menu-anchored-counter.browser.mjs measures it.
const read = (path) => readFile(new URL(`../${path}`, import.meta.url), 'utf8');

test('the items menu renders a count the badge runtime keeps, inside the item container', async () => {
  const template = await read('src/smart/admin-menu/js/templates/default.js');
  // A number is a count kept from the attribute alone: capped, hidden at 0, named.
  assert.match(template, /<span\s+class="sf-badge [^"]*sf-admin-menu-item-counter[^"]*"\s+data-count=\$\{count\}/);
  // A word is a dot with the word as its phrase, when compact.
  assert.match(template, /data-dot\s+data-label=\$\{text\}/);
  // The counter is the container's last child in both modes, so the free
  // admin-menu sheet's compact rule (.sf-admin-menu-item-container > .sf-badge)
  // moves it onto the icon.
  const content = template.slice(template.indexOf('function renderItemContent'), template.indexOf('function renderMenuButton'));
  assert.equal((content.match(/renderCounter\(item, (true|false)\)/g) ?? []).length, 2);
  for (const branch of content.split('html`').slice(1)) {
    const container = branch.slice(branch.indexOf('sf-admin-menu-item-container'), branch.lastIndexOf('</span>'));
    assert.match(container, /renderCounter\(item/);
  }
  // Compact, the container hugs the icon: it is the counter's anchor.
  assert.match(content, /: html`<span\s+class="sf-admin-menu-item-container flex items-cross-center"\s*>/);
});

test('the item is named with its words in both modes', async () => {
  const template = await read('src/smart/admin-menu/js/templates/default.js');
  // The compact item shows no words; its aria-label carries them, and the badge
  // runtime adds the count's phrase: «Входящие, 8 непрочитанных».
  assert.equal((template.match(/aria-label=\$\{itemName\(item, context\.compact\)\}/g) ?? []).length, 2);
  assert.match(template, /function itemName\(item, compact\)/);
});

test('the free sheet still moves the compact counter onto the icon', async () => {
  const free = await read('src/component/admin-menu/scss/_css__admin_menu.scss');
  assert.match(free, /\.sf-admin-menu-panel\.small \.sf-admin-menu-item \.sf-admin-menu-item-container > \.sf-badge:not\(\[hidden\]\) \{\s*@include anchored\.badge;/);
  const styles = await read('src/smart/admin-menu/js/navigation/navigation.css');
  // An item without an icon shows its initials, a round avatar: they stay in the
  // compact menu, and the counter takes their circle's 45-degree point.
  assert.match(styles, /\.sf-admin-menu-panel\.small \.sf-admin-menu-item \.sf-admin-menu-item-container > span\.sf-admin-menu-item-placeholder \{\s*display: flex;/);
  assert.match(styles, /:has\(> \.sf-admin-menu-item-placeholder\) > \.sf-badge \{\s*--sf-badge--anchor-radius: var\(--sf-admin-menu-item-placeholder--border-radius, var\(--sf-radius-rounded\)\);/);
  // Expanded, the words take the room, so the counter is at the row's end.
  assert.match(styles, /\.sf-admin-menu-item-container > \.sf-admin-menu-item-label \{\s*flex: 1 1 auto;/);
});

test('the navigation rail anchors a row\'s counter on its icon', async () => {
  const template = await read('src/smart/admin-menu/js/navigation/template.js');
  const styles = await read('src/smart/admin-menu/js/navigation/navigation.css');
  assert.match(template, /class="sf-badge-anchor sf-badge-anchor--size-1 sf-badge-anchor--square sf-admin-nav-rail-anchor">\$\{icon\(name, "sf-admin-nav-row-icon"\)\}\$\{badge\}<\/span>/);
  assert.match(template, /<span class="sf-badge sf-badge--anchored" data-count=\$\{String\(Math\.trunc\(count\)\)\}><\/span>/);
  assert.match(template, /\$\{compact \? railIcon\(row\.icon, railCounter\(row\)\)/);
  assert.match(template, /railIcon\("notifications", notifications\.unread \? railBadge\(notifications\.unread\) : nothing\)/);
  // The compact notifications row is named by hidden words, the count after them.
  assert.match(template, /<span class="sr-only">\$\{text\("notifications"\)\}<\/span>/);
  // The dot that stood at the row's corner, beside the icon, is gone.
  assert.doesNotMatch(template, /sf-admin-nav-dot/);
  assert.doesNotMatch(styles, /sf-admin-nav-dot/);
  // The ring is the rail's surface.
  assert.match(styles, /\.sf-admin-nav-first \{\s*--sf-badge--surface: var\(--sf-surface-1\);/);
});

test('the badges component that keeps the counts is a relation of the package component', async () => {
  const rule = await read('src/smart/admin-menu/rule.js');
  const manifest = JSON.parse(await read('src/smart/admin-menu/smart.manifest.json'));
  assert.match(rule, /\{ name: "badges" \}/);
  assert.ok(manifest.composition.uses.includes('component.badges'));
  const runtime = await read('src/component/badges/js/_anchored.js');
  assert.match(runtime, /window\.SF\.AnchoredBadge/);
  assert.match(runtime, /attributeFilter: OBSERVED/);
});
