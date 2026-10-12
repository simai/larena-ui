import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import test from 'node:test';

const read = path => readFile(new URL(`../${path}`, import.meta.url), 'utf8');

// Two findings from Larena's stand at 1296x714 (2026-10-12), against mockup v5.

test('the profile menu has a width of its own, wider than the first column', async () => {
  // Since 2026-10-12 it is the Framework's standard menu: the menu sizes to its
  // content up to g2, and the navigation sets its floor above the first column.
  // It took f4 once and cut «Текущий пользователь» and «Безопасность учётной записи».
  const account = await read('src/smart/admin-menu/js/navigation/account.js');
  assert.match(account, /openMenu\(controller, anchor, \{/u);
  const styles = await read('src/smart/admin-menu/js/navigation/navigation.css');
  assert.match(styles, /sf-context-menu\[data-sf-admin-nav-menu\] \{\s*--sf-context-menu--min-inline-size: var\(--sf-f8\);/u,
    'wider than the first column, f6');
  const menu = await read('src/component/context-menu/scss/_css__context_menu.scss');
  assert.match(menu, /max-inline-size: min\(var\(--sf-context-menu--max-inline-size, var\(--sf-g2\)\)/u);
});

test('on a wide screen the first answer opens the root that holds the current row', async () => {
  const controller = await read('src/smart/admin-menu/js/navigation/controller.js');
  const accept = controller.match(/\n    accept\(answer, sequence\) \{([\s\S]*?)\n    \}\n/u)?.[1] ?? '';
  assert.match(accept, /if \(first && this\.wide && !this\.openRoot && !answer\.person\?\.compact\)/u,
    'only on the first answer, so a root the person closed stays closed on a re-read');
  assert.match(accept, /this\.holdsCurrent\(root\)/u);
  assert.match(accept, /root\.key !== answer\.current/u, 'a root that is itself the page has no column to open');
});
