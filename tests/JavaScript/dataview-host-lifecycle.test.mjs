import {test} from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import fs from 'node:fs';
const source = fs.readFileSync(new URL('../../resources/js/admin-smart-event-bridge.js', import.meta.url), 'utf8');
const tick = () => new Promise(resolve => setImmediate(resolve));
class Element extends EventTarget {
    constructor() { super(); this.dataset = {}; this.textContent = ''; this.rows = []; }
    setRows(rows) { this.rows = rows; }
    setState() {} setAttribute() {} setFilterFields() {} setTemplates() {} setTableSettings() {}
}
const search = (table, text) => { const e = new Event('onSearchEnd'); e.detail = text; table.dispatchEvent(e); };
async function harness(count = 1) {
    const pending = []; const observers = [];
    const benches = Array.from({length: count}, () => {
        const table = new Element(), pagination = new Element(), status = new Element();
        const stateNode = {textContent: JSON.stringify({query_endpoint: '/query', profiles: {}, query: {page: 1}})};
        const nodes = {'sf-table': table, 'sf-pagination': pagination, '[data-larena-dataview-state]': stateNode, '[data-larena-dataview-status]': status};
        const host = new Element(); host.querySelector = key => nodes[key] || null; host.querySelectorAll = () => [];
        return {host, table, pagination, status, nodes};
    });
    const document = new EventTarget(); document.readyState = 'complete'; document.documentElement = {};
    let mounted = benches.map(b => b.host);
    document.querySelector = () => null; document.querySelectorAll = selector => selector === '[data-larena-dataview-workbench]' ? mounted : [];
    vm.runInNewContext(source, {document, customElements: {whenDefined: async () => {}}, CSS: {escape: String}, AbortController,
        MutationObserver: class { constructor(fn) {observers.push(fn);} observe() {} },
        fetch: (url, options) => new Promise(resolve => pending.push({url, options, resolve: payload => resolve({ok: true, json: async () => payload})})),
    });
    await tick();
    return {benches, pending, reconcile: () => observers.forEach(fn => fn()), mount: list => {mounted = list; observers.forEach(fn => fn());}};
}
const response = id => ({query: {page: 1}, rows: [{id}], pagination: {page: 1, total: 1, pageSize: 10}});
test('repeated discovery is idempotent and latest result wins independently per instance', async () => {
    const h = await harness(2); h.reconcile(); h.reconcile();
    search(h.benches[0].table, 'first'); search(h.benches[0].table, 'second'); search(h.benches[1].table, 'other');
    assert.equal(h.pending.length, 3);
    h.pending[1].resolve(response('new')); h.pending[2].resolve(response('other')); await tick();
    h.pending[0].resolve(response('old')); await tick();
    assert.equal(h.benches[0].table.rows[0].id, 'new'); assert.equal(h.benches[1].table.rows[0].id, 'other');
    h.mount([]);
});
test('unmount aborts pending requests, removes listeners and suppresses late writes; remount binds once', async () => {
    const h = await harness(); const b = h.benches[0]; search(b.table, 'pending');
    const statusBeforeRemoval = b.status.textContent; h.mount([]);
    assert.equal(h.pending[0].options.signal.aborted, true);
    search(b.table, 'detached'); assert.equal(h.pending.length, 1);
    h.pending[0].resolve(response('late')); await tick();
    assert.deepEqual(b.table.rows, []); assert.equal(b.status.textContent, statusBeforeRemoval);
    h.mount([b.host]); h.reconcile(); search(b.table, 'remounted'); assert.equal(h.pending.length, 2);
    h.pending[1].resolve(response('restored')); await tick(); assert.equal(b.table.rows[0].id, 'restored'); h.mount([]);
});
test('replacing a child in a retained host disposes old binding and connects replacement', async () => {
    const h = await harness(); const b = h.benches[0], old = b.table;
    b.table = b.nodes['sf-table'] = new Element(); h.reconcile();
    search(old, 'old'); search(b.table, 'replacement'); assert.equal(h.pending.length, 1); h.mount([]);
});
