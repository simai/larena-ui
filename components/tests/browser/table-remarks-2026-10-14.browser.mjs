#!/usr/bin/env node
// Usage: node this-file /absolute/path/to/output
// The owner's remarks of 2026-10-14, measured on the built bytes:
// - the column settings window is as wide as what it holds: with two short
//   names it is far narrower than the 24rem every window used to be, and its
//   rows and buttons follow the standard menu's insets and footer;
// - a loading row is as tall as the loaded row it stands for;
// - a click on a link to another record in a cell asks to open it
//   (sf-table-reference-open {field, value, href}) and, when taken, loads no
//   page; a click with a modifier is left to the browser.
import {resolve, join} from 'node:path';
import {chromium} from 'playwright';

if (!process.argv[2]) throw new Error('Build output root is required');
const out = resolve(process.argv[2]);
const styles = [
  'core/core/css/core.css', 'core/core/css/utility.full.css',
  'component/component/pagination/css/pagination.css',
  'component/component/inputs/css/inputs.css',
  'component/component/context-menu/css/context-menu.css',
  'component/component/checkbox/css/checkbox.css',
  'component/component/skeleton/css/skeleton.css',
  'smart/smart/table/css/table.css',
  'smart/smart/sortable/css/sortable.css',
].map((p) => join(out, p));
const modules = [
  'core/core/js/core.js', 'core/core/js/smart-base.js', 'smart/smart/icons/js/icons.js', 'smart/smart/buttons/js/buttons.js',
  'smart/smart/icon-buttons/js/icon-buttons.js', 'smart/smart/checkbox/js/checkbox.js',
  'smart/smart/list-item/js/list-item.js', 'smart/smart/dropdown/js/dropdown.js',
  'smart/smart/inputs/js/inputs.js', 'smart/smart/spinner/js/spinner.js',
  'smart/smart/context-menu/js/context-menu.js', 'smart/smart/sortable/js/sortable.js',
  'smart/smart/pagination/js/pagination.js', 'smart/smart/table/js/table.js',
].map((p) => join(out, p));

const failures = [];
const note = (detail) => failures.push(detail);
const browser = await chromium.launch();

const open = async (size) => {
  const page = await browser.newPage({viewport: {width: 1280, height: 800}});
  await page.setContent(
    `<!doctype html><html class="theme-light" lang="ru"><body style="margin:0">
       <div style="width:1200px"><sf-table id="t" size="${size}"></sf-table></div></body></html>`,
  );
  for (const path of styles) await page.addStyleTag({path}).catch(() => {});
  for (const path of modules) await page.addScriptTag({path, type: 'module'}).catch(() => {});
  await page.waitForTimeout(1100);
  return page;
};

try {
  // 1. The column settings window of a table with two short columns.
  {
    const page = await open('1/2');
    await page.evaluate(() => {
      const table = document.getElementById('t');
      table.setColumns([{key: 'name', label: 'Название'}, {key: 'note', label: 'Описание'}]);
      table.setRows([{id: '1', name: 'A', note: 'B'}]);
    });
    await page.waitForTimeout(500);
    const opened = await page.evaluate(() => {
      const host = [...document.querySelectorAll('#t sf-button, #t sf-icon-button')]
        .find((node) => node.getAttribute('aria-label') === 'Настройки' || node.getAttribute('text') === 'Настройки');
      (host?.querySelector('button') ?? host)?.click();
      return Boolean(host);
    });
    if (!opened) throw new Error('no settings control');
    await page.waitForTimeout(700);
    const window = await page.evaluate(() => {
      const surface = document.querySelector('sf-context-menu .sf-table-context-menu');
      if (!surface) return null;
      const footer = surface.querySelector('.sf-context-menu-footer');
      return {
        width: Math.round(surface.getBoundingClientRect().width),
        size: surface.closest('sf-context-menu')?.getAttribute('size'),
        footer: Boolean(footer),
      };
    });
    console.log(JSON.stringify({window}));
    if (!window) note({case: 'column settings', expected: 'the window opens'});
    else {
      if (window.width >= 360) note({case: 'column settings width', got: window.width, expected: 'as wide as two short names, well under 24rem'});
      if (window.size !== '1/2') note({case: 'column settings size', got: window.size, expected: "the table's size, 1/2"});
      if (!window.footer) note({case: 'column settings footer', expected: 'the standard footer'});
    }
    await page.close();
  }

  // 1b. A list of one data column: the spare room goes to that column, not to
  //     the box and menu columns (Larena, 2026-10-14: «Название» began some
  //     330px in).
  {
    const page = await open('1/2');
    const offset = await page.evaluate(async () => {
      const table = document.getElementById('t');
      table.setColumns([{key: 'name', label: 'Название'}]);
      table.setRows([{id: '1', name: 'Раздел', actions: ['view']}, {id: '2', name: 'Другой раздел', actions: ['view']}]);
      await new Promise((done) => setTimeout(done, 800));
      const grid = document.querySelector('#t table').getBoundingClientRect();
      const name = document.querySelector('#t thead th[data-key="name"]').getBoundingClientRect();
      return {start: Math.round(name.left - grid.left), width: Math.round(name.width), table: Math.round(grid.width)};
    });
    console.log(JSON.stringify({offset}));
    if (offset.start > 160 || offset.width < offset.table / 2) {
      note({case: 'one data column', got: offset, expected: 'the data column near the start, holding the spare room'});
    }
    await page.close();
  }

  // 2. A loading row is as tall as the row it stands for.
  {
    const page = await open('1/2');
    const heights = await page.evaluate(async () => {
      const table = document.getElementById('t');
      table.setColumns([{key: 'name', label: 'Название'}, {key: 'city', label: 'Город'}]);
      table.setDataState('loading');
      await new Promise((done) => setTimeout(done, 400));
      const loading = document.querySelector('#t tbody.sf-table-skeleton tr')?.getBoundingClientRect().height ?? null;
      table.setDataState('auto');
      table.setRows([{id: '1', name: 'Строка', city: 'Город', actions: ['view']}]);
      await new Promise((done) => setTimeout(done, 400));
      const loaded = document.querySelector('#t tbody tr[data-row-id]')?.getBoundingClientRect().height ?? null;
      return {loading, loaded};
    });
    console.log(JSON.stringify({heights}));
    if (heights.loading !== null && heights.loaded !== null && Math.abs(heights.loading - heights.loaded) > 1) {
      note({case: 'loading row', got: heights, expected: 'a loading row as tall as a loaded one'});
    }
    await page.close();
  }

  // 3. A link to another record in a cell asks to open it.
  {
    const page = await open('1/2');
    await page.evaluate(() => {
      const table = document.getElementById('t');
      window.asked = [];
      table.addEventListener('sf-table-reference-open', (event) => {
        window.asked.push(JSON.parse(JSON.stringify(event.detail)));
        event.preventDefault();
      });
      table.setColumns([{key: 'name', label: 'Название'}, {key: 'author', label: 'Автор', renderer: 'link'}]);
      table.setRows([{id: '7', name: 'A', author: {text: 'Administrator', href: '/admin/users/1/edit'}}]);
    });
    await page.waitForTimeout(600);
    const before = page.url();
    const link = page.locator('#t tbody td[data-key="author"] a[href]').first();
    if (!(await link.count())) note({case: 'reference link', expected: 'the cell draws a link'});
    else {
      await link.click();
      await page.waitForTimeout(300);
      const asked = await page.evaluate(() => window.asked);
      console.log(JSON.stringify({asked}));
      if (asked.length !== 1 || asked[0].field !== 'author' || asked[0].href !== '/admin/users/1/edit' || asked[0].record_id !== '7') {
        note({case: 'reference link', got: asked, expected: 'one sf-table-reference-open {record_id 7, field author, href}'});
      }
      if (page.url() !== before) note({case: 'reference link', expected: 'no page loaded when the view takes it'});
    }
    await page.close();
  }
} finally {
  await browser.close();
}
if (failures.length) { console.error(JSON.stringify(failures, null, 2)); process.exit(1); }
console.log('TABLE_REMARKS_2026_10_14_OK');
