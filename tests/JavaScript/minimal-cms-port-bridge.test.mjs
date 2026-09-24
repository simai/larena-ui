import {test} from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import fs from 'node:fs';

const source = fs.readFileSync(new URL('../../resources/js/sf-runtime-bridge.js', import.meta.url), 'utf8');
const tick = () => new Promise(resolve => setImmediate(resolve));

async function harness() {
    const pending = [];
    const status = {hidden: true, dataset: {}, textContent: ''};
    const records = {dataset: {larenaRowRevisions: '{}', larenaMatchedCount: '3', larenaBulkAll: '1'}};
    const host = {querySelector: selector => selector === '[data-larena-row-revisions]' ? records : null};
    const form = {
        dataset: {larenaPortUrl: '/admin/cms/port', larenaPreferencesCsrf: 'csrf', larenaPortActionLabels: '{}'},
        querySelector: selector => ({'[name="scope_ref"]': {value: 'scope:cms'},
            '[name="structure_id"]': {value: 'workbench.demo_solutions'},
            '[data-larena-port-status]': status})[selector] || null,
        closest: selector => selector === '[data-larena-minimal-cms]' ? host : null,
    };
    class Table extends EventTarget {
        constructor() { super(); this.rows = []; this.applied = []; this.pendingSequence = null; this.states = []; this.dataset = {}; this.isConnected = true; this.localName = 'sf-table'; }
        closest(selector) { return selector === 'form[data-larena-dataview-query]' ? form : null; }
        setTableData() {} setRows(rows) { this.rows = rows; }
        applyQueryResult(sequence, rows) {
            if (sequence !== this.pendingSequence) return false;
            this.pendingSequence = null; this.applied.push(sequence); this.rows = rows; this.setDataState('auto'); return true;
        }
        setDataState(state) { this.states.push(state); }
        setAttribute() {} querySelectorAll() { return []; }
    }
    const table = new Table();
    const descriptor = {dataset: {}, textContent: JSON.stringify({target: 'cms-table', component: 'sf-table', props: {data: {rows: []}}})};
    const document = new EventTarget();
    document.readyState = 'complete'; document.documentElement = {lang: 'ru', dataset: {}};
    document.getElementById = id => id === 'cms-table' ? table : null;
    document.querySelectorAll = selector => selector === 'script[type="application/json"][data-larena-smart-hydration]' ? [descriptor] : [];
    document.querySelector = () => null;
    const window = new EventTarget(); window.location = {href: 'http://localhost/admin/cms'};
    const context = {document, window, AbortController, URL, Map, Set, JSON, Object,
        customElements: {whenDefined: async () => {}},
        CustomEvent: class extends Event { constructor(name, options = {}) { super(name); this.detail = options.detail; } },
        MutationObserver: class { observe() {} disconnect() {} },
        fetch: (url, options) => new Promise(resolve => pending.push({url, options,
            resolve: (payload, ok = true) => resolve({ok, json: async () => payload})})),
    };
    vm.runInNewContext(source, context);
    await tick();
    return {table, status, records, pending};
}

function intent(table, sequence, ids) {
    table.pendingSequence = sequence;
    const event = new Event('sf-table-query-intent');
    event.detail = {reason: 'context', sequence, context: {record_ids: ids}};
    table.dispatchEvent(event);
}
const answer = (sequence, ids) => ({answer: 'applied', sequence, data: {
    columns: [{key: 'summary'}], records: ids.map(id => ({id, revision: 1, values: {summary: id}, actions: []})),
    total: ids.length,
}});

test('context query uses the scoped port and the newest answer wins', async () => {
    const h = await harness();
    intent(h.table, 1, ['record-a']);
    assert.equal(h.status.dataset.state, 'loading');
    intent(h.table, 2, ['record-b']);
    assert.equal(h.pending.length, 2);
    assert.equal(h.pending[0].url, '/admin/cms/port');
    assert.deepEqual(JSON.parse(h.pending[1].options.body), {scope_ref: 'scope:cms',
        structure_id: 'workbench.demo_solutions', intent: 'query.change',
        payload: {sequence: 2, query: {record_ids: ['record-b']}}});
    h.pending[1].resolve(answer(2, ['record-b']));
    await tick();
    h.pending[0].resolve(answer(1, ['record-a']));
    await tick();
    assert.deepEqual(h.table.applied, [2]);
    assert.deepEqual(h.table.rows.map(row => row.id), ['record-b']);
    assert.equal(h.status.dataset.state, 'populated');
    assert.equal(h.records.dataset.larenaMatchedCount, '1');
    assert.equal(h.records.dataset.larenaBulkAll, '');
});

test('empty answer declares empty state and outage preserves the previous rows', async () => {
    const h = await harness();
    intent(h.table, 3, []);
    h.pending[0].resolve(answer(3, []));
    await tick();
    assert.deepEqual(h.table.rows, []);
    assert.equal(h.status.dataset.state, 'empty');
    intent(h.table, 4, ['record-x']);
    h.pending[1].resolve({answer: 'unavailable', reason: 'store_down'}, false);
    await tick();
    assert.equal(h.status.dataset.state, 'error');
    assert.equal(h.table.states.at(-1), 'error');
    assert.deepEqual(h.table.applied, [3]);
});
