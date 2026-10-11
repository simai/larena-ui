#!/usr/bin/env node
// Usage: node this-file /absolute/path/to/output
//
// A data column stops shrinking before it stops being readable, and the table
// scrolls instead.
//
// A fixed-layout table divides whatever room it has and will divide it to
// nothing: in 620px with five columns they came out 61px each, which shreds
// «Код решения» over three lines and cannot hold a date. The consumer asked for
// a floor and for the horizontal scroll to take over (Larena, 2026-10-06), and
// the admin reference showed it happening (owner, 2026-10-09).
import {resolve, join} from 'node:path';
import {existsSync} from 'node:fs';
import {chromium} from 'playwright';

if (!process.argv[2]) throw new Error('Build output root is required');
const out = resolve(process.argv[2]);
const styles = ['core/core/css/core.css', 'core/core/css/utility.full.css',
  'smart/smart/table/css/table.css'].map((p) => join(out, p));
const modules = [
  'core/core/js/core.js', 'smart/smart/icons/js/icons.js',
  'smart/smart/buttons/js/buttons.js', 'smart/smart/icon-buttons/js/icon-buttons.js',
  'smart/smart/checkbox/js/checkbox.js', 'smart/smart/spinner/js/spinner.js',
  'smart/smart/context-menu/js/context-menu.js', 'smart/smart/list-item/js/list-item.js',
  'smart/smart/pagination/js/pagination.js', 'smart/smart/table/js/table.js',
].map((p) => join(out, p));

const absent = [...styles, ...modules].filter((path) => !existsSync(path));
if (absent.length) {
  console.error(JSON.stringify({missingAssets: absent, root: out}, null, 2));
  throw new Error(`${absent.length} build asset(s) not found under ${out}`);
}

const failures = [];
const browser = await chromium.launch();

try {
  // Narrow on purpose: five columns in 420px is where the squeezing was.
  const page = await browser.newPage({viewport: {width: 420, height: 700}});
  await page.setContent(
    '<!doctype html><html class="theme-light" lang="ru"><body style="margin:0">'
    + '<div id="box" style="inline-size:420px"><sf-table id="t" toolbar="false" aria-label="Проба"></sf-table></div>'
    + '</body></html>',
  );
  for (const style of styles) await page.addStyleTag({path: style});
  for (const module of modules) await page.addScriptTag({path: module, type: 'module'});
  await page.waitForFunction(() => Boolean(customElements.get('sf-table')));

  await page.evaluate(() => {
    const table = document.getElementById('t');
    table.setColumns([
      {key: 'title', label: 'Название'},
      {key: 'code', label: 'Код решения'},
      {key: 'section', label: 'Раздел'},
      {key: 'status', label: 'Статус'},
      {key: 'updated', label: 'Обновлено'},
    ]);
    table.setRows([{id: '1', title: 'Раз', code: 'simai.one', section: 'Первый', status: 'В работе', updated: '2026-12-25'}]);
  });
  await page.waitForFunction(() => document.querySelectorAll('#t tbody tr').length === 1, null, {timeout: 8000});

  const measured = await page.evaluate(() => {
    const table = document.querySelector('#t table');
    const floor = Number.parseFloat(getComputedStyle(table).getPropertyValue('--sf-table--column-min-inline-size'))
      * Number.parseFloat(getComputedStyle(document.documentElement).fontSize);
    const widths = [...table.querySelectorAll('thead th')].map((th) => Math.round(th.getBoundingClientRect().width));
    let node = table.parentElement;
    let scroller = null;
    while (node && node !== document.body) {
      if (/(auto|scroll)/.test(getComputedStyle(node).overflowX)) { scroller = node; break; }
      node = node.parentElement;
    }
    return {
      floor: Math.round(floor),
      dataWidths: widths.slice(-5),
      scrollBy: scroller ? scroller.scrollWidth - scroller.clientWidth : 0,
    };
  });

  // A build with no floor at all answers NaN here, and every comparison below
  // would quietly be false -- which is how the first version of this probe
  // passed on the very bytes it was written against.
  if (!Number.isFinite(measured.floor) || measured.floor <= 0) {
    failures.push({said: 'the table declares no column floor', floor: measured.floor});
  }

  const narrow = measured.dataWidths.filter((width) => width < measured.floor - 1);
  if (narrow.length) {
    failures.push({said: 'a data column is narrower than the floor', floor: measured.floor, widths: measured.dataWidths});
  }
  // The point of the floor is that the table scrolls rather than squeezing: in
  // 420px five columns at the floor cannot fit, so it has to.
  if (measured.scrollBy <= 0) {
    failures.push({said: 'the columns do not fit and the table does not scroll', measured});
  }

  console.log(JSON.stringify({
    probe: 'column-readable-floor',
    measured,
    result: failures.length ? 'COLUMN_FLOOR_FAIL' : 'COLUMN_FLOOR_OK',
    failures,
  }, null, 2));
} finally {
  await browser.close();
}

if (failures.length) process.exit(1);
