import {test} from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import fs from 'node:fs';

// The host side of a composition route: a list that is the target of a layout.scope
// route shows only the records its scope delivers and answers through applyQueryResult.
const source = fs.readFileSync(new URL('../../resources/js/admin-smart-event-bridge.js', import.meta.url), 'utf8');
const tick = () => new Promise(resolve => setImmediate(resolve));
class Element extends EventTarget {
    constructor() { super(); this.dataset = {}; this.textContent = ''; this.rows = []; this.applied = []; this.pendingSequence = null; }
    setRows(rows) { this.rows = rows; }
    applyQueryResult(sequence, rows) {
        if (sequence !== this.pendingSequence) return false;
        this.pendingSequence = null; this.rows = rows; this.applied.push(sequence);
        return true;
    }
    setState() {} setAttribute() {} setFilterFields() {} setTemplates() {} setTableSettings() {}
}
const routes = [{id: 'pick', from: {endpoint: 'records', port: 'selection'}, to: {endpoint: 'selected', port: 'context'}}];

async function harness(endpoint) {
    const pending = [];
    const table = new Element(), pagination = new Element(), status = new Element();
    const stateNode = {textContent: JSON.stringify({query_endpoint: '/query', profiles: {}, query: {page: 1, filters: {}}})};
    const nodes = {'sf-table': table, 'sf-pagination': pagination, '[data-larena-dataview-state]': stateNode, '[data-larena-dataview-status]': status};
    const scope = {getAttribute: name => name === 'data-sf-routes' ? JSON.stringify(routes) : null};
    const endpointHost = {getAttribute: name => name === 'data-sf-endpoint' ? endpoint : null,
        closest: selector => selector === 'sf-composition-scope' ? scope : null};
    const host = new Element();
    host.querySelector = key => nodes[key] || null; host.querySelectorAll = () => [];
    host.closest = selector => selector === '[data-sf-endpoint]' ? endpointHost : null;
    const document = new EventTarget(); document.readyState = 'complete'; document.documentElement = {};
    let mounted = [host];
    document.querySelector = () => null;
    document.querySelectorAll = selector => selector === '[data-larena-dataview-workbench]' ? mounted : [];
    const observers = [];
    const context = {Map, document, CustomEvent: class extends Event { constructor(name, options = {}) { super(name); this.detail = options.detail; } },
        customElements: {whenDefined: async () => {}}, CSS: {escape: String}, AbortController,
        MutationObserver: class { constructor(fn) { observers.push(fn); } observe() {} disconnect() {} },
        fetch: (url, options) => new Promise(resolve => pending.push({url, options, resolve: payload => resolve({ok: true, json: async () => payload})})),
    };
    vm.runInNewContext(source, context);
    await tick();
    return {table, status, pending, unmount: () => { mounted = []; observers.forEach(fn => fn()); }};
}
const intent = (table, ids, sequence) => {
    table.pendingSequence = sequence;
    const event = new Event('sf-table-query-intent');
    event.detail = Object.freeze({reason: 'context', context: Object.freeze({record_ids: ids}), sequence});
    table.dispatchEvent(event);
};
const search = (table, text) => { const e = new Event('onSearchEnd'); e.detail = text; table.dispatchEvent(e); };
const answer = ids => ({query: {page: 1, filters: {record_id: {operator: 'in', value: ids}}}, rows: ids.map(id => ({id})), pagination: {page: 1, total: ids.length, pageSize: 10}});
const body = request => JSON.parse(request.options.body).query;

test('a route target starts empty and never queries without a delivered selection', async () => {
    const h = await harness('selected');
    assert.equal(h.status.dataset.state, 'empty');
    search(h.table, 'anything');
    await tick();
    assert.equal(h.pending.length, 0);
    assert.equal(h.table.rows.length, 0);
    h.unmount();
});

test('a delivered selection becomes one server query answered through the table sequence', async () => {
    const h = await harness('selected');
    intent(h.table, ['record-a', 'record-b'], 3);
    assert.equal(h.pending.length, 1);
    assert.deepEqual(body(h.pending[0]).filters, {record_id: {operator: 'in', value: ['record-a', 'record-b']}});
    assert.equal(body(h.pending[0]).page, 1);
    h.pending[0].resolve(answer(['record-a', 'record-b']));
    await tick();
    assert.deepEqual(h.table.applied, [3]);
    assert.deepEqual(h.table.rows.map(row => row.id), ['record-a', 'record-b']);
    // A later search keeps the delivered selection as a server filter.
    search(h.table, 'alp');
    assert.deepEqual(body(h.pending[1]).filters, {record_id: {operator: 'in', value: ['record-a', 'record-b']}});
    h.unmount();
});

test('latest selection wins: an older answer arriving last changes nothing', async () => {
    const h = await harness('selected');
    intent(h.table, ['record-a'], 1);
    intent(h.table, ['record-b'], 2);
    assert.equal(h.pending.length, 2);
    h.pending[1].resolve(answer(['record-b']));
    await tick();
    h.pending[0].resolve(answer(['record-a']));
    await tick();
    assert.deepEqual(h.table.applied, [2]);
    assert.deepEqual(h.table.rows.map(row => row.id), ['record-b']);
    h.unmount();
});

test('an empty or oversized selection is answered locally without a request', async () => {
    const h = await harness('selected');
    intent(h.table, [], 4);
    assert.equal(h.pending.length, 0);
    assert.deepEqual(h.table.applied, [4]);
    intent(h.table, Array.from({length: 101}, (_, index) => `record-${index}`), 5);
    assert.equal(h.pending.length, 0);
    assert.deepEqual(h.table.applied, [4, 5]);
    assert.equal(h.status.dataset.state, 'error');
    h.unmount();
});

test('a list that is only a route source ignores context intents', async () => {
    const h = await harness('records');
    intent(h.table, ['record-a'], 1);
    assert.equal(h.pending.length, 0);
    search(h.table, 'alp');
    assert.equal(body(h.pending[0]).filters?.record_id, undefined);
    h.unmount();
});

test('an unmounted target sends nothing and applies no late answer', async () => {
    const h = await harness('selected');
    intent(h.table, ['record-a'], 1);
    h.unmount();
    assert.equal(h.pending[0].options.signal.aborted, true);
    h.pending[0].resolve(answer(['record-a']));
    await tick();
    assert.deepEqual(h.table.applied, []);
    intent(h.table, ['record-b'], 2);
    assert.equal(h.pending.length, 1);
});
