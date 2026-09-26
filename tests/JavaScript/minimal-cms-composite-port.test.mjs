import {test} from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import fs from 'node:fs';

const source = fs.readFileSync(new URL('../../resources/js/sf-runtime-bridge.js', import.meta.url), 'utf8');
const tick = () => new Promise(resolve => setImmediate(resolve));

async function harness(data, {bulkAll = false, storageWorkbench = false} = {}) {
    const calls = [];
    const surface = {hidden: true};
    const legacy = {open: true};
    const heading = {textContent: 'Items found: 3'};
    const badgeText = {textContent: '450'};
    const badge = {attributes: {text: '450', 'aria-label': 'Элементы: 450'},
        getAttribute(name) { return this.attributes[name] ?? null; },
        setAttribute(name, value) { this.attributes[name] = value; },
        querySelector(selector) { return selector === '.sf-badge-text' ? badgeText : null; }};
    const records = {dataset: {larenaFilterFields: JSON.stringify([{key: 'owner', label: 'Owner', filter: {control: 'entity-multi-select'}}]),
        larenaPortCapabilities: JSON.stringify(storageWorkbench ? [] : ['saved-views', 'row-actions']),
        larenaRowRevisions: '{}', larenaMatchedCount: '0', larenaBulkAll: bulkAll ? '1' : '',
        larenaStorageWorkbenchComposite: storageWorkbench ? '1' : ''},
    querySelector: selector => selector === '[data-larena-composite-surface]' ? surface : null,
    closest: selector => selector === '.larena-workbench-records'
        ? {querySelector: target => target === '[data-larena-storage-legacy]' ? legacy
            : target === '.larena-workbench-section-header p' ? heading : null} : null};
    const inputs = {scope_ref: {value: 'scope:cms'}, structure_id: {value: 'workbench.demo_solutions'},
        filters: {value: '{}'}, search: {value: ''}, sort_field: {value: ''}, per_page: {value: '20'},
        include_archived: {value: '1'}};
    const form = {dataset: {larenaPortUrl: storageWorkbench ? '/admin/storage-workbench/port' : '/admin/cms/port', larenaPreferencesCsrf: 'csrf',
        larenaPaginationQuery: JSON.stringify({page: 1, per_page: 20, search: 'needle', sort_field: 'owner', sort_direction: 'desc'})},
    querySelector: selector => ({'[name="scope_ref"]': inputs.scope_ref,
        '[name="structure_id"]': inputs.structure_id})[selector] ?? null,
    elements: {namedItem: name => inputs[name] ?? null}};
    const table = {setFilterFields(fields) { this.fields = fields; }};
    const pagination = {attributes: {}, setAttribute(name, value) { this.attributes[name] = value; }};
    const view = {dataset: {}, table, pagination, closest: selector => selector === 'form[data-larena-dataview-query]' ? form
        : selector === '[data-larena-row-revisions]' ? records : null,
    setHostPort(port) { this.port = port; }};
    const document = {readyState: 'complete', documentElement: {dataset: {}},
        getElementById: id => id === 'minimal-cms-record-count' ? badge : null,
        querySelector: selector => selector === 'sf-data-view' ? view : null,
        querySelectorAll: selector => selector === 'sf-data-view' ? [view] : []};
    const window = {location: new URL('http://localhost/admin/cms'), dispatchEvent() {}, confirm: () => true};
    const context = {document, window, URL, JSON, Object, AbortController,
        customElements: {whenDefined: async () => {}},
        MutationObserver: class { observe() {} disconnect() {} },
        CustomEvent: class { constructor(type) { this.type = type; } },
        fetch: async (url, options) => { calls.push({url, options}); return {json: async () =>
            typeof data === 'function' ? data(calls.length) : data}; }};
    vm.runInNewContext(source, context);
    await tick();
    return {view, table, pagination, records, calls, surface, legacy, badge, badgeText, heading};
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
    assert.equal(h.badge.attributes.text, '1');
    assert.equal(h.badge.attributes['aria-label'], 'Элементы: 1');
    assert.equal(h.badgeText.textContent, '1');
    assert.equal(h.calls[0].url, 'http://localhost/admin/cms/port');
    assert.deepEqual(JSON.parse(h.calls[0].options.body), {scope_ref: 'scope:cms',
        structure_id: 'workbench.demo_solutions', intent: 'query.change',
        payload: {sequence: 1, query: JSON.parse(JSON.stringify(h.view.query))}});
});

test('all-record action appears only with the host permission', async () => {
    const allowed = await harness({answer: 'applied'}, {bulkAll: true});
    const denied = await harness({answer: 'applied'});
    assert.equal(allowed.pagination.attributes['show-action-for-all'], '');
    assert.equal(denied.pagination.attributes['show-action-for-all'], undefined);
});

test('selected bulk carries one or two revisions through the scoped host port', async () => {
    const h = await harness({answer: 'refused', reason: 'host_decides'});
    for (const revisions of [{one: 3}, {one: 3, two: 5}]) {
        const ids = Object.keys(revisions);
        const answer = await h.view.port.raise('bulk.apply_selected', {
            action_id: 'bulk_delete', record_ids: ids, revisions,
        }, {});
        assert.equal(answer.answer, 'refused');
        const call = h.calls.at(-1);
        assert.equal(call.options.headers['X-CSRF-TOKEN'], 'csrf');
        assert.deepEqual(JSON.parse(call.options.body), {
            scope_ref: 'scope:cms', structure_id: 'workbench.demo_solutions',
            intent: 'bulk.apply_selected',
            payload: {action_id: 'archive', record_ids: ids, revisions},
        });
    }
});

test('Storage workbench selected archive confirms on the owner port and hides unsupported row actions', async () => {
    const h = await harness({answer: 'applied', sequence: 1, data: {
        columns: [{key: 'owner'}], total: 1,
        records: [{id: 'record-1', revision: 3, actions: ['record.delete'],
            values: {owner: 'user:secret'}, display_values: {owner: 'Alice'}}],
        echo: {filters_chosen: {}, search: '', sort: [], page: 1, per_page: 20},
    }}, {storageWorkbench: true});
    assert.equal(h.view.query.include_archived, true);
    assert.deepEqual(Array.from(h.view.port.capabilities), []);
    assert.equal(h.surface.hidden, false);
    assert.equal(h.legacy.open, true);
    const answer = await h.view.port.raise('query.change', {sequence: 1, query: h.view.query}, {});
    assert.equal(h.legacy.open, false);
    assert.equal(h.heading.textContent, 'Items found: 1');
    assert.deepEqual(JSON.parse(JSON.stringify(answer.data.records)), [{id: 'record-1', revision: 3,
        actions: [], owner: 'Alice'}]);
    assert.ok(!JSON.stringify(answer.data.records).includes('user:secret'));
    await h.view.port.raise('bulk.apply_selected', {action_id: 'bulk_delete',
        record_ids: ['record-1'], revisions: {'record-1': 3}}, {});
    assert.equal(h.calls.at(-1).url, 'http://localhost/admin/storage-workbench/port');
    assert.deepEqual(JSON.parse(h.calls.at(-1).options.body).payload, {
        action_id: 'archive', confirmed: true, record_ids: ['record-1'], revisions: {'record-1': 3},
    });
});

test('older query answer cannot restore a stale outer count or row revisions', async () => {
    const replies = [];
    const h = await harness(() => new Promise(resolve => replies.push(resolve)));
    const result = (sequence, total) => ({answer: 'applied', sequence, data: {
        columns: [{key: 'owner'}], total,
        records: [{id: `record-${sequence}`, revision: sequence, actions: [], display_values: {owner: 'Alice'}}],
    }});
    const first = h.view.port.raise('query.change', {sequence: 1, query: {}}, {});
    const second = h.view.port.raise('query.change', {sequence: 2, query: {}}, {});
    await tick();
    replies[1](result(2, 215));
    await second;
    replies[0](result(1, 450));
    await first;
    assert.equal(h.badge.attributes.text, '215');
    assert.equal(h.records.dataset.larenaMatchedCount, '215');
    assert.deepEqual(JSON.parse(h.records.dataset.larenaRowRevisions), {'record-2': 2});
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
