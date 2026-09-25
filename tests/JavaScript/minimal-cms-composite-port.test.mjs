import {test} from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import fs from 'node:fs';

const source = fs.readFileSync(new URL('../../resources/js/sf-runtime-bridge.js', import.meta.url), 'utf8');
const tick = () => new Promise(resolve => setImmediate(resolve));

async function harness(data) {
    const calls = [];
    const records = {dataset: {larenaFilterFields: JSON.stringify([{key: 'owner', label: 'Owner', filter: {control: 'entity-multi-select'}}]),
        larenaPortCapabilities: JSON.stringify(['saved-views', 'row-actions']),
        larenaRowRevisions: '{}', larenaMatchedCount: '0', larenaBulkAll: ''}};
    const inputs = {scope_ref: {value: 'scope:cms'}, structure_id: {value: 'workbench.demo_solutions'},
        filters: {value: '{}'}, search: {value: ''}, sort_field: {value: ''}, per_page: {value: '20'}};
    const form = {dataset: {larenaPortUrl: '/admin/cms/port', larenaPreferencesCsrf: 'csrf',
        larenaPaginationQuery: JSON.stringify({page: 1, per_page: 20, search: 'needle', sort_field: 'owner', sort_direction: 'desc'})},
    querySelector: selector => ({'[name="scope_ref"]': inputs.scope_ref,
        '[name="structure_id"]': inputs.structure_id})[selector] ?? null,
    elements: {namedItem: name => inputs[name] ?? null}};
    const table = {setFilterFields(fields) { this.fields = fields; }};
    const view = {dataset: {}, table, closest: selector => selector === 'form[data-larena-dataview-query]' ? form
        : selector === '[data-larena-row-revisions]' ? records : null,
    setHostPort(port) { this.port = port; }};
    const document = {readyState: 'complete', documentElement: {dataset: {}},
        querySelector: selector => selector === 'sf-data-view' ? view : null,
        querySelectorAll: selector => selector === 'sf-data-view' ? [view] : []};
    const window = {location: new URL('http://localhost/admin/cms'), dispatchEvent() {}, confirm: () => true};
    const context = {document, window, URL, JSON, Object, AbortController,
        customElements: {whenDefined: async () => {}},
        MutationObserver: class { observe() {} disconnect() {} },
        CustomEvent: class { constructor(type) { this.type = type; } },
        fetch: async (url, options) => { calls.push({url, options}); return {json: async () => data}; }};
    vm.runInNewContext(source, context);
    await tick();
    return {view, table, records, calls};
}

test('composite consumes only owner-projected cells and scoped port answers', async () => {
    const h = await harness({answer: 'applied', sequence: 1, data: {
        columns: [{key: 'owner'}], total: 1,
        records: [{id: 'record-1', revision: 3, actions: ['record.view'],
            values: {owner: 'user:secret'}, display_values: {owner: 'Alice'}}],
        echo: {filters_chosen: {}, search: '', sort: [], page: 1, per_page: 20},
    }});
    assert.equal(h.view.port.version, '1.1.0');
    assert.deepEqual(Array.from(h.view.port.capabilities), ['saved-views', 'row-actions']);
    assert.equal(h.view.query.search, 'needle');
    assert.deepEqual(JSON.parse(JSON.stringify(h.view.query.sort)), [{key: 'owner', direction: 'desc'}]);
    assert.equal(h.table.fields[0].key, 'owner');
    const answer = await h.view.port.raise('query.change', {sequence: 1, query: h.view.query}, {});
    assert.equal(answer.answer, 'applied');
    assert.deepEqual(JSON.parse(JSON.stringify(answer.data.records)), [{id: 'record-1', revision: 3,
        actions: ['view'], owner: 'Alice'}]);
    assert.ok(!JSON.stringify(answer.data.records).includes('user:secret'));
    assert.equal(h.calls[0].url, 'http://localhost/admin/cms/port');
    assert.deepEqual(JSON.parse(h.calls[0].options.body), {scope_ref: 'scope:cms',
        structure_id: 'workbench.demo_solutions', intent: 'query.change',
        payload: {sequence: 1, query: JSON.parse(JSON.stringify(h.view.query))}});
});

test('composite view save sends only host-owned fields with revision and full view content', async () => {
    const h = await harness({answer: 'applied'});
    const answer = await h.view.port.raise('view.save', {key: 'draft', label: 'Draft', revision: 2,
        query: {search: 'needle'}, roles: {title: 'owner'},
        layout: {columns: [{key: 'owner'}]}, settings: {density: 'compact'}}, {});
    assert.equal(answer.answer, 'applied');
    assert.deepEqual(JSON.parse(h.calls[0].options.body).payload, {key: 'draft', revision: 2,
        query: {search: 'needle'}, roles: {title: 'owner'},
        layout: {columns: [{key: 'owner'}]}, settings: {density: 'compact'}});
});

test('missing owner display projection refuses the page without exposing raw IDs', async () => {
    const h = await harness({answer: 'applied', sequence: 1, data: {
        columns: [{key: 'owner'}], total: 1,
        records: [{id: 'record-1', revision: 3, actions: [], values: {owner: 'user:secret'}}],
    }});
    const answer = await h.view.port.raise('query.change', {sequence: 1, query: h.view.query}, {});
    assert.equal(answer.answer, 'unavailable');
    assert.equal(answer.reason, 'port_display_projection_missing');
});
