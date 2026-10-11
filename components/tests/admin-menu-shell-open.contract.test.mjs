import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import test from 'node:test';

const read = path => readFile(new URL(`../${path}`, import.meta.url), 'utf8');

// Two findings from Larena's stand at 1296x714 (2026-10-12), against mockup v5.

test('the profile menu has a width of its own, wider than the first column', async () => {
  const styles = await read('src/smart/admin-menu/js/navigation/navigation.css');
  const rule = styles.match(/\n  \.sf-admin-nav-account \{([^}]*)\}/u)?.[1] ?? '';
  // It took f4 and cut «Текущий пользователь» and «Безопасность учётной записи».
  assert.doesNotMatch(rule, /inline-size: var\(--sf-f4\)/u);
  assert.match(rule, /inline-size: max-content;/u);
  assert.match(rule, /min-inline-size: var\(--sf-f8\);/u, 'wider than the first column, f6');
  assert.match(rule, /max-inline-size: min\(var\(--sf-g2\), calc\(100vw - 2 \* var\(--sf-a4\)\)\);/u);
});

test('on a wide screen the first answer opens the root that holds the current row', async () => {
  const controller = await read('src/smart/admin-menu/js/navigation/controller.js');
  const accept = controller.match(/\n    accept\(answer, sequence\) \{([\s\S]*?)\n    \}\n/u)?.[1] ?? '';
  assert.match(accept, /if \(first && this\.wide && !this\.openRoot && !answer\.person\?\.compact\)/u,
    'only on the first answer, so a root the person closed stays closed on a re-read');
  assert.match(accept, /this\.holdsCurrent\(root\)/u);
  assert.match(accept, /root\.key !== answer\.current/u, 'a root that is itself the page has no column to open');
});
