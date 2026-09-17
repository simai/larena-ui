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
async function harness(count = 1, statePatch = {}) {
    const pending = []; const observers = [];
    const benches = Array.from({length: count}, () => {
        const table = new Element(), pagination = new Element(), status = new Element(), reset = new Element();
        const stateNode = {textContent: JSON.stringify({query_endpoint: '/query', profiles: {}, query: {page: 1}, ...statePatch})};
        const nodes = {'sf-table': table, 'sf-pagination': pagination, '[data-larena-dataview-state]': stateNode, '[data-larena-dataview-status]': status, '[data-larena-dataview-reset]': reset};
        const host = new Element(); host.querySelector = key => nodes[key] || null; host.querySelectorAll = () => [];
        return {host, table, pagination, status, reset, nodes};
    });
    const document = new EventTarget(); document.readyState = 'complete'; document.documentElement = {};
    let mounted = benches.map(b => b.host);
    document.querySelector = () => null; document.querySelectorAll = selector => selector === '[data-larena-dataview-workbench]' ? mounted : [];
    const context = {Map, document, CustomEvent: class extends Event { constructor(name, options = {}) { super(name); this.detail = options.detail; } }, customElements: {whenDefined: async () => {}}, CSS: {escape: String}, AbortController,
        MutationObserver: class { constructor(fn) {observers.push(fn);} observe() {} disconnect() {} },
        fetch: (url, options) => new Promise(resolve => pending.push({url, options, resolve: payload => resolve({ok: true, json: async () => payload})})),
    };
    vm.runInNewContext(source, context);
    await tick();
    return {actions: context.LarenaDataviewActions, benches, pending, reconcile: () => observers.forEach(fn => fn()), mount: list => {mounted = list; observers.forEach(fn => fn());}};
}
const response = id => ({query: {page: 1}, rows: [{id}], pagination: {page: 1, total: 1, pageSize: 10}});
test('sibling toolbar Apply and Enter query only their owner; nested and released controls are ignored', async () => {
    const h = await harness(2); const b = h.benches[0];
    const field = {value: 'created'};
    b.nodes['.larena-dataview-toolbar input[name="search"]'] = field;
    const target = {closest: selector => selector === '[data-larena-dataview-workbench]' ? b.host : {}, matches: () => true};
    const action = (type, owner = b.host, composing = false) => {
        const e = new Event(type, {cancelable: true});
        Object.defineProperty(e, 'target', {value: {...target, closest: selector => selector === '[data-larena-dataview-workbench]' ? owner : {}}});
        e.key = 'Enter'; e.isComposing = composing; b.host.dispatchEvent(e); return e;
    };
    assert.equal(action('click').defaultPrevented, true);
    assert.deepEqual(JSON.parse(h.pending[0].options.body).query, {page: 1, search: 'created'});
    field.value = ''; action('keydown');
    assert.equal(JSON.parse(h.pending[1].options.body).query.search, '');
    for (const [name, value] of Object.entries({filter_field: 'caption', filter_value: 'Exact', sort_field: 'caption', sort_direction: 'desc', per_page: '20'})) {
        b.nodes[`.larena-dataview-toolbar input[name="${name}"]`] = {value};
    }
    action('click');
    assert.deepEqual(JSON.parse(h.pending[2].options.body).query, {page: 1, search: '', filters: {caption: {operator: 'eq', value: 'Exact'}}, sort: [{field: 'caption', direction: 'desc'}], page_size: 20});
    action('click', h.benches[1].host); action('keydown', b.host, true);
    assert.equal(h.pending.length, 3);
    h.pending[2].resolve(response('own')); await tick();
    assert.deepEqual(h.benches[1].table.rows, []);
    h.mount([]); action('click'); assert.equal(h.pending.length, 3);
});
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

const emit = (element, name, detail) => { const event = new Event(name); event.detail = detail; element.dispatchEvent(event); };
test('trusted refresh retains owning current search and page; a removed registration cannot refresh', async () => {
    const h = await harness(2); const b = h.benches[0];
    const release = h.actions.register(b.table, new Map([['view', async () => ({state: 'cancelled'})]]));
    const current = {...response('existing'), query: {page: 2, search: 'current', page_size: 20}};
    search(b.table, 'current'); h.pending[0].resolve(current); await tick();
    const pending = h.actions.refresh(b.table);
    assert.equal(h.pending.length, 2); assert.equal(h.pending[1].url, '/query');
    assert.deepEqual(JSON.parse(h.pending[1].options.body).query, current.query);
    h.pending[1].resolve(response('refreshed')); assert.equal(await pending, true);
    assert.equal(b.table.rows[0].id, 'refreshed'); assert.deepEqual(h.benches[1].table.rows, []);
    release(); assert.equal(await h.actions.refresh(b.table), false); assert.equal(h.pending.length, 2); h.mount([]);
});
test('query-only page size changes query without requesting an absent persistence endpoint', async () => {
    const h = await harness(); const b = h.benches[0];
    emit(b.pagination, 'sf-page-size-change', {pageSize: 20});
    assert.equal(h.pending.length, 1);
    assert.equal(h.pending[0].url, '/query');
    assert.equal(h.pending[0].options.method, 'POST');
    assert.equal(JSON.parse(h.pending[0].options.body).query.page_size, 20);
    h.pending[0].resolve(response('resized')); await tick();
    assert.equal(b.status.dataset.state, 'success'); h.mount([]);
});
test('a connected preferences store still receives page size changes', async () => {
    const h = await harness(1, {user_endpoint: '/preferences'}); const b = h.benches[0];
    emit(b.pagination, 'sf-page-size-change', {pageSize: 20});
    assert.equal(h.pending.length, 2);
    assert.equal(h.pending[0].url, '/preferences');
    assert.equal(h.pending[0].options.method, 'PUT');
    assert.equal(JSON.parse(h.pending[0].options.body).profile.pagination.page_size, 20);
    assert.equal(h.pending[1].url, '/query'); h.mount([]);
});
test('explicit save without a store reports unavailable and makes no request', async () => {
    const h = await harness(); const b = h.benches[0];
    emit(b.table, 'onColumnSettingsChange', {columnSettings: {title: {visible: true}}});
    await tick(); assert.equal(h.pending.length, 0);
    assert.equal(b.status.dataset.state, 'unavailable'); h.mount([]);
});

test('unregistered bulk intent is unavailable and cannot imply backend success', async () => {
    const h = await harness(); const b = h.benches[0]; let forwarded = 0;
    b.table.addEventListener('sf-data-view-bulk-action', () => forwarded++);
    emit(b.pagination, 'sf-action-apply', {action: 'archive', ids: [1]});
    assert.equal(forwarded, 0);
    assert.equal(h.pending.length, 0);
    assert.equal(b.status.dataset.state, 'unavailable'); h.mount([]);
});

test('reset without a persistence endpoint makes no request and reports unavailable', async () => {
    const h = await harness(); const b = h.benches[0];
    emit(b.reset, 'click'); await tick();
    assert.equal(h.pending.length, 0); assert.equal(b.status.dataset.state, 'unavailable'); h.mount([]);
});
test('reset reloads rows with the effective default page size and cannot claim query success prematurely', async () => {
    const h = await harness(1, {user_endpoint: '/preferences', profiles: {user: {revision: 3}, effective: {pagination: {page_size: 20}}}});
    const b = h.benches[0]; emit(b.reset, 'click');
    assert.equal(h.pending[0].url, '/preferences'); assert.equal(h.pending[0].options.method, 'DELETE');
    assert.equal(JSON.parse(h.pending[0].options.body).base_revision, 3);
    h.pending[0].resolve({profiles: {user: {revision: 4}, effective: {pagination: {page_size: 10}}}}); await tick();
    assert.equal(h.pending[1].url, '/query');
    const query = JSON.parse(h.pending[1].options.body).query;
    assert.equal(query.page, 1); assert.equal(query.page_size, 10);
    assert.equal(b.status.dataset.state, 'loading');
    h.pending[1].resolve(response('reset-result')); await tick();
    assert.equal(b.table.rows[0].id, 'reset-result'); assert.equal(b.status.dataset.state, 'success'); h.mount([]);
});

test('saved reset with invalid dataset response reports error instead of success', async () => {
    const h = await harness(1, {user_endpoint: '/preferences'}); const b = h.benches[0];
    emit(b.reset, 'click');
    h.pending[0].resolve({profiles: {effective: {pagination: {page_size: 10}}}}); await tick();
    h.pending[1].resolve({query: {page: 1}, rows: [], pagination: null}); await tick();
    assert.equal(b.status.dataset.state, 'error');
    assert.match(b.status.textContent, /Не удалось загрузить данные/); h.mount([]);
});

const intent = (table, ids, action = 'archive', extras = {}) => emit(table, 'sf-table-action-intent', {action_id: action, record_ids: ids, ...extras});
test('typed owned intent preserves opaque IDs including zero and waits for owner result', async () => {
    const h = await harness(); const b = h.benches[0]; let seen; let resolve;
    h.actions.register(b.table, new Map([['archive', arg => {seen = arg; return new Promise(r => {resolve = r;});}]]));
    intent(b.table, ['record_a_b', 'Юникод_记录', 0]);
    assert.equal(b.status.dataset.state, 'pending');
    assert.deepEqual(Array.from(seen.record_ids), ['record_a_b', 'Юникод_记录', 0]);
    assert.equal(Object.isFrozen(seen.record_ids), true);
    resolve({state: 'cancelled'}); await tick(); assert.equal(b.status.dataset.state, 'cancelled'); h.mount([]);
});
test('unknown actions and invalid payloads cannot call owner handler', async () => {
    const h = await harness(); const b = h.benches[0]; let calls = 0;
    h.actions.register(b.table, new Map([['archive', () => {calls++;}]]));
    intent(b.table, [0], 'unknown'); assert.equal(b.status.dataset.state, 'unavailable');
    for (const ids of [[], [''], [1.5], [Number.MAX_SAFE_INTEGER + 1], [null]]) intent(b.table, ids);
    intent(b.table, [0], 'archive', {endpoint: '/foreign'});
    assert.equal(calls, 0); assert.equal(b.status.dataset.state, 'error'); h.mount([]);
});
test('nested and sibling component intents cannot enter another instance handler', async () => {
    const h = await harness(2); let calls = 0; const b = h.benches[0];
    h.actions.register(b.table, new Map([['archive', () => {calls++;}]]));
    intent(h.benches[1].table, [0]);
    const e = new Event('sf-table-action-intent'); e.detail = {action_id: 'archive', record_ids: [0]};
    e.composedPath = () => [h.benches[1].table, b.table]; b.table.dispatchEvent(e);
    assert.equal(calls, 0); h.mount([]);
});
test('unmount aborts owner operation and prevents late result; remount needs registration', async () => {
    const h = await harness(); const b = h.benches[0]; let seen, resolve, calls = 0;
    h.actions.register(b.table, new Map([['archive', arg => {seen = arg; calls++; return new Promise(r => {resolve = r;});}]]));
    intent(b.table, [0]); intent(b.table, [0]); assert.equal(calls, 1);
    h.mount([]); assert.equal(seen.signal.aborted, true);
    const before = b.status.textContent; resolve({state: 'completed'}); await tick(); assert.equal(b.status.textContent, before);
    h.mount([b.host]); intent(b.table, [0]); assert.equal(b.status.dataset.state, 'unavailable'); h.mount([]);
});
test('bulk current selection uses table port and all-result scope fails closed', async () => {
    const h = await harness(); const b = h.benches[0]; let seen;
    h.actions.register(b.table, new Map([['archive', arg => {seen = arg; return {state: 'cancelled'};}]]));
    b.table.getSelectedRecordIds = () => ['opaque_a_b', 0];
    b.table.requestActionIntent = (action, ids) => intent(b.table, ids, action);
    emit(b.pagination, 'sf-action-apply', {action: 'archive', actionForAll: true});
    assert.equal(seen, undefined); assert.equal(b.status.dataset.state, 'unavailable');
    emit(b.pagination, 'sf-action-apply', {action: 'archive', actionForAll: false, ids: ['forged']});
    await tick(); assert.deepEqual(Array.from(seen.record_ids), ['opaque_a_b', 0]); h.mount([]);
});
test('handler resolution alone is not business success; rejected handler reports error', async () => {
    const h = await harness(); const b = h.benches[0];
    h.actions.register(b.table, new Map([['archive', () => undefined]])); intent(b.table, [0]); await tick();
    assert.equal(b.status.dataset.state, 'unavailable');
    h.actions.register(b.table, new Map([['archive', () => {throw new Error('private owner detail');}]])); intent(b.table, [0]); await tick();
    assert.equal(b.status.dataset.state, 'error'); assert.doesNotMatch(b.status.textContent, /private/); h.mount([]);
});

test('trusted owner conflicts and access refusals are actionable without leaking private error text', async () => {
    const h = await harness(); const b = h.benches[0];
    for (const [status, expected] of [[409, /Конфликт версии/], [403, /Недостаточно прав/], [503, /Не удалось/]]) {
        h.actions.register(b.table, new Map([['archive', () => {throw Object.assign(new Error('private database detail'), {status});}]]));
        intent(b.table, [0]); await tick();
        assert.equal(b.status.dataset.state, 'error');
        assert.match(b.status.textContent, expected); assert.doesNotMatch(b.status.textContent, /private|database/);
    }
    h.mount([]);
});
test('unregister cancels pending operation immediately and replacement cannot receive stale completion', async () => {
    const h = await harness(); const b = h.benches[0]; let old, oldResolve, newResolve;
    const release = h.actions.register(b.table, new Map([['archive', arg => {old = arg; return new Promise(r => {oldResolve = r;});}]]));
    intent(b.table, [0]); release(); assert.equal(old.signal.aborted, true); assert.equal(b.status.dataset.state, 'cancelled');
    h.actions.register(b.table, new Map([['archive', () => new Promise(r => {newResolve = r;})]]));
    intent(b.table, ['new']); assert.equal(b.status.dataset.state, 'pending');
    oldResolve({state: 'completed'}); await tick(); assert.equal(b.status.dataset.state, 'pending');
    newResolve({state: 'cancelled'}); await tick(); assert.equal(b.status.dataset.state, 'cancelled'); h.mount([]);
});

test('explicit shadow host routes exact owner only, unregister aborts pending and remount requires fresh registration', async () => {
    const h = await harness(2); const b = h.benches[1]; h.mount([h.benches[0].host]);
    b.host.isConnected = true; b.host.getRootNode = () => ({});
    let calls = 0, input, resolve;
    const release = h.actions.registerHost(b.host, b.table, new Map([['archive', value => {calls++; input=value; return new Promise(r=>{resolve=r;});}]]));
    await tick();
    const foreign = new Event('sf-table-action-intent'); foreign.detail={action_id:'archive',record_ids:['nested_γ_03']}; foreign.composedPath=()=>[h.benches[0].table,b.table]; b.table.dispatchEvent(foreign);
    assert.equal(calls,0); intent(b.table,['nested_γ_03']); assert.equal(calls,1);
    release(); assert.equal(input.signal.aborted,true); intent(b.table,['nested_γ_03']); assert.equal(calls,1);
    resolve({state:'completed'}); await tick();
    const release2=h.actions.registerHost(b.host,b.table,new Map([['archive',()=>{calls++;return {state:'cancelled'};}]]));
    await tick(); intent(b.table,[0]); assert.equal(calls,2); await tick(); release(); intent(b.table,[0]); assert.equal(calls,3);
    b.host.isConnected=false; h.reconcile(); intent(b.table,[0]); assert.equal(calls,3); release2(); h.mount([]);
});
test('explicit registration rejects mismatched table or disconnected host', async () => {
    const h=await harness(2); const b=h.benches[0]; b.host.isConnected=true;b.host.getRootNode=()=>({});
    assert.throws(()=>h.actions.registerHost(b.host,h.benches[1].table,new Map()), /exact table/);
    b.host.isConnected=false;assert.throws(()=>h.actions.registerHost(b.host,b.table,new Map()), /connected/);h.mount([]);
});

test('owner discovery skips nested pagination and state even when they occur first', async () => {
    const h=await harness(); const b=h.benches[0]; const owned=b.nodes['sf-pagination']; const foreign=new Element();
    foreign.closest=()=>({}); owned.closest=()=>b.host;
    b.host.querySelector=key=>key==='sf-pagination'?foreign:b.nodes[key]||null;
    b.host.querySelectorAll=key=>key==='sf-pagination'?[foreign,owned]:[];
    h.reconcile(); emit(owned,'sf-page-size-change',{pageSize:20});assert.equal(h.pending.length,1);
    emit(foreign,'sf-page-size-change',{pageSize:30});assert.equal(h.pending.length,1);h.mount([]);
});
