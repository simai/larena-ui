import {test} from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import fs from 'node:fs';

const source = fs.readFileSync(new URL('../../resources/js/sf-runtime-bridge.js', import.meta.url), 'utf8');
const tick = () => new Promise(resolve => setImmediate(resolve));

async function harness(data, {bulkAll = false, storageWorkbench = false, openViewInQuery = false} = {}) {
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
        larenaStorageWorkbenchComposite: storageWorkbench ? '1' : '', larenaOpenViewInQuery: openViewInQuery ? '1' : ''},
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
    listeners: {}, addEventListener(type, listener) { this.listeners[type] = listener; },
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
    assert.equal(h.view.port.version, '1.4.0');
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
    assert.equal(h.badgeText.textContent, '450', 'the badge markup is left to the badge');
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
            // The person confirmed in the dialog, so every host port receives the confirmation.
            payload: {action_id: 'archive', confirmed: true, record_ids: ids, revisions},
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
    assert.deepEqual(JSON.parse(h.calls[0].options.body).payload, {key: 'draft', label: 'Draft', revision: 2,
        query: {search: 'needle'}, roles: {title: 'owner'},
        layout: {columns: [{key: 'owner'}]}, settings: {density: 'compact'}});
});

test('a view saved with «Save for all» ticked in the table form goes to the host as a view for everyone', async () => {
    const h = await harness({answer: 'applied'});
    const save = key => h.view.port.raise('view.save', {key, label: 'Ours', query: {}}, {});
    h.view.listeners['sf-table-template-save']({detail: {key: 'filter_template_1', label: 'Ours', data: {template_save_for_all: '1'}}});
    await save('filter_template_1');
    // The choice is used once: a later save of the composite's own is the person's view.
    await save('filter_template_1');
    h.view.listeners['sf-table-template-save']({detail: {key: 'filter_template_2', label: 'Mine', data: {}}});
    await save('filter_template_2');
    assert.deepEqual(h.calls.map(call => JSON.parse(call.options.body).payload.target), ['shared', undefined, undefined]);
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

test('a saved record writes only its own row again, asking the page that holds it once', async () => {
    const page = (owner, revision) => ({answer: 'applied', data: {
        columns: [{key: 'owner'}], total: 2,
        records: [{id: 'record-1', revision: 1, actions: [], display_values: {owner: 'Alice'}},
            {id: 'record-2', revision, actions: [], display_values: {owner}}],
    }});
    const h = await harness((call) => (call === 1 ? page('Bob', 1) : page('Carol', 2)));
    const patched = [];
    let refreshed = 0;
    h.view.updateRecord = (id, row) => { patched.push([id, row]); return true; };
    h.view.refresh = () => { refreshed += 1; };
    await h.view.port.raise('query.change', {sequence: 1, query: {page: 1, per_page: 20, search: 'needle'}}, {});
    assert.equal(await h.view.larenaRefreshRecord('record-2'), true);
    assert.equal(refreshed, 0);
    assert.equal(JSON.parse(h.calls[1].options.body).payload.query.search, 'needle');
    assert.equal(patched.length, 1);
    assert.equal(patched[0][0], 'record-2');
    assert.equal(patched[0][1].owner, 'Carol');
    assert.deepEqual(JSON.parse(h.records.dataset.larenaRowRevisions), {'record-1': 1, 'record-2': 2});
    // A record the shown rows do not hold asks the table for its page again.
    assert.equal(await h.view.larenaRefreshRecord('record-9'), false);
    assert.equal(refreshed, 1);
});

test('column settings answers reach the data view as they are, and the Storage list keeps an open saved view', async () => {
    const settings = {columns: [{key: 'owner', visible: false}], column_settings: {layers: {personal: {revision: 2, columns: {owner: {visible: false}}}},
        target: {kind: 'personal', revision: 2, key: null}}};
    const h = await harness(count => count === 1 ? {answer: 'applied', data: settings}
        : count === 2 ? {answer: 'applied', data: {columns: [{key: 'owner'}], total: 0, records: []}}
            : {answer: 'applied', data: settings}, {openViewInQuery: true});
    const saved = await h.view.port.raise('settings.save_personal', {columnSettings: {owner: {visible: false}}, base_revision: 1}, {});
    assert.deepEqual(JSON.parse(JSON.stringify(saved.data)), settings, 'an answer without rows is not projected');
    assert.equal(JSON.parse(h.calls[0].options.body).payload.saved_view_id, undefined);
    await h.view.port.raise('view.open', {key: 'saved-view-aaaaaaaaaaaaaaaaaaaaaaaa'}, {});
    assert.equal(h.view.query.saved_view_id, 'saved-view-aaaaaaaaaaaaaaaaaaaaaaaa');
    await h.view.port.raise('settings.save_personal', {columnSettings: {}, base_revision: 1}, {});
    assert.equal(JSON.parse(h.calls[2].options.body).payload.saved_view_id, 'saved-view-aaaaaaaaaaaaaaaaaaaaaaaa',
        'a change while a saved view is open is written into that view');
    await h.view.port.raise('view.delete', {key: 'saved-view-aaaaaaaaaaaaaaaaaaaaaaaa'}, {});
    assert.equal(h.view.query.saved_view_id, undefined);
    const cms = await harness({answer: 'applied'});
    await cms.view.port.raise('view.open', {key: 'saved-view-aaaaaaaaaaaaaaaaaaaaaaaa'}, {});
    assert.equal(cms.view.query.saved_view_id, undefined, 'the Minimal CMS list keeps its own behaviour');
});
