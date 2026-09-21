(() => {
    'use strict';

    const selector = 'sf-admin-menu[persistence-mode="external"]';
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const endpoint = (menu, target, reset = false) => {
        if (target === 'system') {
            return menu.getAttribute(reset ? 'system-settings-reset-endpoint' : 'system-settings-endpoint') || '';
        }
        return menu.getAttribute(reset ? 'settings-reset-endpoint' : 'settings-endpoint') || '';
    };

    const request = async (url, options = {}) => {
        if (!url.startsWith('/')) {
            throw Object.assign(new Error('smart_event_endpoint_invalid'), {status: 422});
        }
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            ...options,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw Object.assign(new Error(payload.error || 'smart_event_request_failed'), {
                status: response.status,
                payload,
            });
        }
        return payload;
    };

    const menuFromEvent = (event) => event.target?.matches?.(selector) ? event.target : null;

    const applyCompactLayout = (menu, compact) => {
        const shell = menu?.closest?.('[data-larena-admin-shell]');
        if (!shell) return;
        shell.dataset.larenaNavigationCompact = compact ? 'true' : 'false';
    };

    document.addEventListener('sf-admin-menu-compact-change', (event) => {
        const menu = menuFromEvent(event);
        if (!menu) return;
        applyCompactLayout(menu, event.detail?.compact === true);
    });

    customElements.whenDefined('sf-admin-menu').then(() => {
        document.querySelectorAll(selector).forEach((menu) => {
            applyCompactLayout(menu, menu.getProp?.('compact') === true || menu.hasAttribute('compact'));
        });
    });

    const refresh = async (menu, target) => {
        const url = menu.getAttribute('settings-state-endpoint') || '';
        const state = await request(url, {method: 'GET', headers: {Accept: 'application/json'}});
        menu.applyExternalSettingsState?.(state, target);
        return state;
    };

    document.addEventListener('sf-admin-menu-settings-save', async (event) => {
        const menu = menuFromEvent(event);
        if (!menu) return;
        const target = event.detail?.target === 'system' ? 'system' : 'user';
        try {
            const result = await request(endpoint(menu, target), {
                method: 'PUT',
                body: JSON.stringify({
                    base_revision: event.detail?.base_revision,
                    profile: event.detail?.profile,
                }),
            });
            menu.applySettingsResult?.(result);
        } catch (error) {
            menu.applySettingsError?.({
                ...(error.payload || {}),
                error: error.message,
                status: error.status || 500,
            });
        }
    });

    document.addEventListener('sf-admin-menu-settings-reset', async (event) => {
        const menu = menuFromEvent(event);
        if (!menu) return;
        const target = event.detail?.target === 'system' ? 'system' : 'user';
        try {
            const result = await request(endpoint(menu, target, true), {
                method: 'DELETE',
                body: JSON.stringify({base_revision: event.detail?.base_revision}),
            });
            menu.applySettingsResult?.(result);
        } catch (error) {
            menu.applySettingsError?.({
                ...(error.payload || {}),
                error: error.message,
                status: error.status || 500,
            });
        }
    });

    for (const eventName of ['sf-admin-menu-settings-mode-change', 'sf-admin-menu-settings-refresh']) {
        document.addEventListener(eventName, async (event) => {
            const menu = menuFromEvent(event);
            if (!menu) return;
            const target = event.detail?.target === 'system' ? 'system' : 'user';
            try {
                await refresh(menu, target);
            } catch (error) {
                menu.applySettingsError?.({
                    ...(error.payload || {}),
                    error: error.message,
                    status: error.status || 500,
                });
            }
        });
    }

    const workbenchSelector = '[data-larena-dataview-workbench]';
    const ownNode = (host, selector) => {
        const first = host.querySelector(selector);
        if (!first?.closest || first.closest(workbenchSelector) === host) return first;
        return [...host.querySelectorAll(selector)].find(node => node.closest(workbenchSelector) === host) || null;
    };
    const deepMerge = (base, patch) => {
        const result = {...(base || {})};
        Object.entries(patch || {}).forEach(([key, value]) => {
            result[key] = value && typeof value === 'object' && !Array.isArray(value)
                ? deepMerge(result[key] || {}, value)
                : value;
        });
        return result;
    };

    // Trusted product code registers closures by exact table instance, never JSON or DOM IDs.
    const actionRegistrations = new WeakMap();
    const explicitHosts = new Map();
    let reconcileDataview = () => {};
    const actionPort = Object.freeze({
        refresh(table) {
            const registration = actionRegistrations.get(table);
            if (!registration || registration.controller.signal.aborted) return Promise.resolve(false);
            const instance = [...dataviewInstances.values()].find(item => item.table === table);
            return instance?.refresh?.() ?? Promise.resolve(false);
        },
        registerHost(host, table, handlers) {
            if (!host?.isConnected || ownNode(host, 'sf-table') !== table
                || !ownNode(host, '[data-larena-dataview-state]')
                || !ownNode(host, 'sf-pagination') || !ownNode(host, '[data-larena-dataview-status]')) {
                throw new TypeError('Expected connected owner host with exact table and complete state');
            }
            explicitHosts.get(host)?.release();
            const unregister = actionPort.register(table, handlers);
            const observer = new MutationObserver(() => reconcileDataview());
            const registration = {table, release: () => {
                observer.disconnect();
                unregister();
                if (explicitHosts.get(host) !== registration) return;
                explicitHosts.delete(host);
                dataviewInstances.get(host)?.dispose();
                dataviewInstances.delete(host);
            }};
            explicitHosts.set(host, registration);
            observer.observe(host.getRootNode(), {childList: true, subtree: true});
            Promise.resolve().then(() => reconcileDataview());
            return registration.release;
        },
        register(table, handlers) {
            if (!table?.addEventListener || !(handlers instanceof Map)
                || [...handlers].some(([id, handler]) => typeof id !== 'string' || !id.trim() || typeof handler !== 'function')) {
                throw new TypeError('Expected table instance and registered action handlers');
            }
            actionRegistrations.get(table)?.controller.abort();
            const registration = {handlers: new Map(handlers), controller: new AbortController()};
            actionRegistrations.set(table, registration);
            return () => {
                registration.controller.abort();
                if (actionRegistrations.get(table) === registration) actionRegistrations.delete(table);
            };
        },
    });
    Object.defineProperty(globalThis, 'LarenaDataviewActions', {value: actionPort});

    const dataviewInstances = new Map();

    const initDataviewWorkbench = (workbench) => {
        if (dataviewInstances.has(workbench)) return;
        const stateNode = ownNode(workbench, '[data-larena-dataview-state]');
        const table = ownNode(workbench, 'sf-table');
        const pagination = ownNode(workbench, 'sf-pagination');
        const status = ownNode(workbench, '[data-larena-dataview-status]');
        if (!stateNode || !table || !pagination || !status) return;
        const state = JSON.parse(stateNode.textContent || '{}');
        const controller = new AbortController();
        let disposed = false;
        const listen = (target, name, handler, options = {}) => {
            target?.addEventListener(name, handler, {...(typeof options === 'boolean' ? {capture: options} : options), signal: controller.signal});
        };
        dataviewInstances.set(workbench, {table, pagination, stateNode, refresh: () => runQuery(), dispose: () => {
            disposed = true;
            querySequence++;
            controller.abort();
            actionRegistrations.get(table)?.controller.abort();
            actionRegistrations.delete(table);
        }});
        let mode = 'user';
        let profiles = state.profiles || {};
        let queryState = state.query || {search: '', filters: {}, page: 1, page_size: 10};
        let querySequence = 0;

        const setStatus = (text, kind = 'ready') => {
            if (disposed) return;
            status.textContent = text;
            status.dataset.state = kind;
            workbench.dataset.larenaDataviewState = kind;
        };
        const profileForMode = () => mode === 'system'
            ? deepMerge(profiles.package || {}, profiles.system?.profile || profiles.system || {})
            : profiles.effective || deepMerge(deepMerge(profiles.package || {}, profiles.system?.profile || {}), profiles.user?.profile || {});
        let canonicalFilterFields = [];
        const applyProfile = (profile) => {
            table.setTableSettings?.({columnSettings: profile.columns || {}}, 'larena-profile', false);
            const filterSettings = profile.filters?.fields || {};
            table.setFilterFields?.(canonicalFilterFields
                .filter((field) => filterSettings[field.key]?.visible !== false)
                .sort((left, right) => (filterSettings[left.key]?.order ?? 1000) - (filterSettings[right.key]?.order ?? 1000)), 'larena-profile-filter-fields');
            const selected = profile.filters?.selected_template_key || '';
            table.setTemplates?.((profile.filters?.templates || []).map((template) => ({...template, selected: template.key === selected})));
        };
        const applyResponseProfiles = (payload) => {
            if (payload.profiles) {
                profiles = payload.profiles.effective ? payload.profiles : {
                    ...profiles,
                    [payload.target]: {revision: payload.revision, profile: payload.profile},
                };
                if (!profiles.effective) {
                    profiles.effective = deepMerge(deepMerge(profiles.package || {}, profiles.system?.profile || {}), profiles.user?.profile || {});
                }
            }
            applyProfile(profileForMode());
        };

        const hydration = document.querySelector(`script[data-larena-smart-hydration="${CSS.escape(table.id)}"]`);
        const descriptor = hydration ? JSON.parse(hydration.textContent || '{}') : {};
        const data = descriptor.props?.data || {};
        canonicalFilterFields = data.filterFields || [];
        table.setFilterFields?.(canonicalFilterFields, 'larena-filter-fields');
        table.setTemplates?.(data.templates || []);
        table.setTableSettings?.(data.tableSettings || {}, 'larena-table-settings', false);
        applyProfile(profileForMode());

        const updatePagination = (page) => {
            pagination.setState?.({current: page.page, total: page.total, pageSize: page.pageSize});
            pagination.setAttribute('current', String(page.page));
            pagination.setAttribute('total', String(page.total));
            pagination.setAttribute('page-size', String(page.pageSize));
        };
        // A list that is the target of a composition route shows only the records
        // its scope delivers. The route is read from the published scope markup.
        const endpointHost = workbench.closest?.('[data-sf-endpoint]') ?? null;
        let scopeRoutes = [];
        try {
            scopeRoutes = JSON.parse(endpointHost?.closest?.('sf-composition-scope')?.getAttribute('data-sf-routes') || '[]');
        } catch {
            scopeRoutes = [];
        }
        let contextIds = endpointHost && Array.isArray(scopeRoutes)
            && scopeRoutes.some((route) => route?.to?.endpoint === endpointHost.getAttribute('data-sf-endpoint')) ? [] : null;
        let contextSequence = null;
        const contextQuery = () => {
            const filters = {...(queryState.filters || {})};
            delete filters.record_id;
            if (contextIds?.length) filters.record_id = {operator: 'in', value: contextIds};
            queryState = {...queryState, filters};
        };
        const showRows = (rows) => {
            // The answer to a pending context request is applied through the table's
            // sequence check, so a superseded answer never replaces newer rows.
            if (contextSequence === null) {
                table.setRows?.(rows, 'larena-query');
                return true;
            }
            const applied = table.applyQueryResult?.(contextSequence, rows) === true;
            contextSequence = null;
            return applied;
        };
        const runQuery = async (patch = {}) => {
            if (disposed) return;
            queryState = {...queryState, ...patch};
            const sequence = ++querySequence;
            if (contextIds !== null) {
                contextQuery();
                if (!contextIds.length) {
                    queryState = {...queryState, page: 1};
                    showRows([]);
                    updatePagination({page: 1, total: 0, pageSize: queryState.page_size || 10});
                    setStatus('Выберите записи в связанном списке', 'empty');
                    return true;
                }
            }
            setStatus('Загрузка…', 'loading');
            try {
                const payload = await request(state.query_endpoint, {method: 'POST', body: JSON.stringify({query: queryState}), signal: controller.signal});
                if (disposed || sequence !== querySequence) return;
                queryState = payload.query;
                if (!showRows(payload.rows || [])) return;
                updatePagination(payload.pagination);
                setStatus(payload.pagination.total ? `Найдено: ${payload.pagination.total}` : 'Ничего не найдено', payload.pagination.total ? 'success' : 'empty');
                return true;
            } catch (error) {
                if (disposed || sequence !== querySequence) return;
                if (contextSequence !== null) showRows([]);
                setStatus(error.status === 409 ? 'Конфликт версии — обновите состояние' : 'Не удалось загрузить данные. Попробуйте снова.', 'error');
                return false;
            }
        };
        const saveProfile = async (profile) => {
            if (disposed) return;
            const target = mode === 'system' ? profiles.system : profiles.user;
            const endpointUrl = mode === 'system' ? state.system_endpoint : state.user_endpoint;
            if (!endpointUrl) {
                setStatus('Сохранение настроек не подключено', 'unavailable');
                return;
            }
            setStatus('Сохранение…', 'saving');
            try {
                const payload = await request(endpointUrl, {
                    method: 'PUT',
                    signal: controller.signal,
                    body: JSON.stringify({base_revision: target?.revision || 0, profile}),
                });
                if (disposed) return;
                applyResponseProfiles(payload);
                setStatus('Настройки сохранены', 'success');
            } catch (error) {
                if (disposed) return;
                setStatus(error.status === 409 ? 'Конфликт версии — обновите состояние' : 'Не удалось сохранить. Черновик оставлен.', 'error');
            }
        };
        const currentProfile = () => JSON.parse(JSON.stringify(profileForMode()));

        listen(table, 'sf-table-query-intent', (event) => {
            if (!ownedEvent(event, table) || disposed || contextIds === null) return;
            const detail = event.detail;
            const ids = detail?.context?.record_ids;
            if (detail?.reason !== 'context' || !Number.isSafeInteger(detail.sequence) || !Array.isArray(ids)) return;
            if (ids.length > 100) {
                contextSequence = detail.sequence;
                contextIds = [];
                showRows([]);
                setStatus('Выбрано слишком много записей: не больше 100', 'error');
                return;
            }
            contextIds = [...ids];
            contextSequence = detail.sequence;
            runQuery({page: 1});
        });

        let actionPending = false;
        let activeOperation = null;
        const ownedEvent = (event, component) => {
            const origin = event.composedPath?.()[0] || event.target;
            return origin === component;
        };
        listen(table, 'sf-table-action-intent', async (event) => {
            if (!ownedEvent(event, table) || disposed) return;
            const detail = event.detail;
            const valid = detail && typeof detail === 'object' && !Array.isArray(detail)
                && Object.keys(detail).length === 2
                && Object.hasOwn(detail, 'action_id') && Object.hasOwn(detail, 'record_ids')
                && typeof detail.action_id === 'string' && detail.action_id.trim()
                && Array.isArray(detail.record_ids) && detail.record_ids.length > 0
                && Array.from(detail.record_ids).every(id => typeof id === 'string' && id.length > 0 || Number.isSafeInteger(id));
            if (!valid) { setStatus('Некорректный запрос действия', 'error'); return; }
            const registration = actionRegistrations.get(table);
            const handler = registration?.handlers.get(detail.action_id);
            if (!handler || registration.controller.signal.aborted) {
                setStatus('Действие не подключено', 'unavailable'); return;
            }
            if (actionPending) return;
            actionPending = true;
            const operation = new AbortController();
            activeOperation = operation;
            const abort = () => {
                operation.abort();
                if (activeOperation === operation) {
                    activeOperation = null;
                    actionPending = false;
                    setStatus('Действие отменено', 'cancelled');
                }
            };
            controller.signal.addEventListener('abort', abort, {once: true});
            registration.controller.signal.addEventListener('abort', abort, {once: true});
            setStatus('Выполняется действие…', 'pending');
            try {
                const result = await handler(Object.freeze({
                    action_id: detail.action_id,
                    record_ids: Object.freeze([...detail.record_ids]),
                    signal: operation.signal,
                    table,
                    refresh: () => disposed || operation.signal.aborted ? Promise.resolve(false) : runQuery(),
                }));
                if (disposed || actionRegistrations.get(table) !== registration || activeOperation !== operation) return;
                if (operation.signal.aborted || result?.state === 'cancelled') setStatus('Действие отменено', 'cancelled');
                else if (result?.state === 'completed') setStatus('Действие завершено', 'success');
                else setStatus('Результат действия не подтверждён', 'unavailable');
            } catch (error) {
                if (disposed || actionRegistrations.get(table) !== registration || activeOperation !== operation) return;
                const message = operation.signal.aborted ? 'Действие отменено'
                    : error?.status === 409 ? 'Конфликт версии — обновите запись перед повторным редактированием'
                    : error?.status === 403 ? 'Недостаточно прав для действия'
                    : 'Не удалось выполнить действие';
                setStatus(message, operation.signal.aborted ? 'cancelled' : 'error');
            } finally {
                controller.signal.removeEventListener('abort', abort);
                registration.controller.signal.removeEventListener('abort', abort);
                if (activeOperation === operation) {
                    activeOperation = null;
                    actionPending = false;
                }
            }
        });

        // The registered toolbar is a sibling of the grid, not its event parent.
        // Delegate through the owning host so hydration can replace native controls.
        const toolbarSearch = (includeOptions = false) => {
            const input = ownNode(workbench, '.larena-dataview-toolbar input[name="search"]');
            if (!input || typeof input.value !== 'string') return;
            const patch = {search: input.value, page: 1};
            if (includeOptions) {
                const value = name => ownNode(workbench, `.larena-dataview-toolbar input[name="${name}"]`)?.value;
                const field = value('filter_field'), filter = value('filter_value');
                if (typeof field === 'string' && field !== '__unavailable' && typeof filter === 'string') {
                    patch.filters = filter === '' ? {} : {[field]: {operator: 'eq', value: filter}};
                }
                const sort = value('sort_field'), direction = value('sort_direction');
                if (typeof sort === 'string' && sort !== '__unavailable' && ['asc', 'desc'].includes(direction)) {
                    patch.sort = [{field: sort, direction}];
                }
                const size = value('per_page');
                if (typeof size === 'string' && /^(10|20|50|100)$/.test(size)) patch.page_size = Number(size);
            }
            runQuery(patch);
        };
        listen(workbench, 'click', (event) => {
            if (event.target?.closest?.(workbenchSelector) !== workbench
                || !event.target.closest('.larena-dataview-toolbar')
                || !event.target.closest('button[aria-label="Apply table query"]')) return;
            event.preventDefault();
            toolbarSearch(true);
        });
        listen(workbench, 'keydown', (event) => {
            if (event.key !== 'Enter' || event.isComposing
                || event.target?.closest?.(workbenchSelector) !== workbench
                || !event.target.closest('.larena-dataview-toolbar')
                || !event.target.matches?.('input[name="search"]')) return;
            event.preventDefault();
            toolbarSearch();
        });
        listen(table, 'onSearchEnd', (event) => runQuery({search: String(event.detail || ''), page: 1}));
        listen(table, 'onFilterUpdate', (event) => runQuery({filters: event.detail?.values || {}, page: 1}));
        listen(table, 'onTemplateSave', (event) => {
            const profile = currentProfile();
            const incoming = Array.isArray(event.detail) ? event.detail : [event.detail];
            const byKey = new Map((profile.filters?.templates || []).map((item) => [item.key, item]));
            incoming.filter((item) => item?.key).forEach((item) => byKey.set(item.key, item));
            const selectedTemplate = incoming.find((item) => item?.selected) || incoming[0] || null;
            const selected = selectedTemplate?.key || profile.filters?.selected_template_key || '';
            profile.filters = {...(profile.filters || {}), templates: Array.from(byKey.values()), selected_template_key: selected};
            saveProfile(profile);
            if (selectedTemplate?.data?.values && typeof selectedTemplate.data.values === 'object') {
                runQuery({filters: selectedTemplate.data.values, page: 1});
            }
        });
        listen(table, 'onColumnSettingsChange', (event) => {
            const profile = currentProfile();
            profile.columns = event.detail?.columnSettings || {};
            saveProfile(profile);
        });
        listen(pagination, 'sf-page-change', (event) => runQuery({page: event.detail?.current || 1}));
        listen(pagination, 'sf-page-size-change', (event) => {
            const profile = currentProfile();
            profile.pagination = {page_size: event.detail?.pageSize || 10};
            if (mode === 'system' ? state.system_endpoint : state.user_endpoint) saveProfile(profile);
            runQuery({page: 1, page_size: event.detail?.pageSize || 10});
        });
        listen(pagination, 'sf-show-more', (event) => runQuery({page: Math.min((event.detail?.current || 1) + 1, event.detail?.pageCount || 1)}));
        listen(pagination, 'sf-action-apply', (event) => {
            if (!ownedEvent(event, pagination)) return;
            const actionId = event.detail?.action;
            if (event.detail?.actionForAll === true) {
                setStatus('Действие для всех записей не подключено', 'unavailable'); return;
            }
            if (typeof actionId !== 'string' || !actionRegistrations.get(table)?.handlers.has(actionId)
                || typeof table.requestActionIntent !== 'function' || typeof table.getSelectedRecordIds !== 'function') {
                setStatus('Действие не подключено', 'unavailable'); return;
            }
            try {
                const ids = table.getSelectedRecordIds();
                if (!ids.length) { setStatus('Выберите записи', 'unavailable'); return; }
                table.requestActionIntent(actionId, ids);
            } catch { setStatus('Некорректный запрос действия', 'error'); }
        });

        workbench.querySelectorAll('[data-larena-dataview-mode-button]').forEach((button) => {
            const targetMode = button.dataset.larenaDataviewModeButton;
            const allowed = targetMode !== 'system' || state.capabilities?.save_system === true;
            button.disabled = !allowed;
            listen(button, 'click', () => {
                mode = targetMode;
                workbench.dataset.larenaDataviewMode = mode;
                workbench.querySelectorAll('[data-larena-dataview-mode-button]').forEach((candidate) => candidate.classList.toggle('larena-button-primary', candidate === button));
                applyProfile(profileForMode());
                setStatus(mode === 'system' ? 'Режим настроек для всех' : 'Режим личных настроек');
            });
        });
        listen(ownNode(workbench, '[data-larena-dataview-reset]'), 'click', async () => {
            const target = mode === 'system' ? profiles.system : profiles.user;
            const endpointUrl = mode === 'system' ? state.system_endpoint : state.user_endpoint;
            if (!endpointUrl) {
                setStatus('Сброс настроек не подключён', 'unavailable');
                return;
            }
            setStatus('Сброс…', 'saving');
            try {
                const payload = await request(endpointUrl, {method: 'DELETE', body: JSON.stringify({base_revision: target?.revision || 0}), signal: controller.signal});
                if (disposed) return;
                applyResponseProfiles(payload);
                const pageSize = profileForMode().pagination?.page_size || 10;
                await runQuery({page: 1, page_size: pageSize});
            } catch (error) {
                if (disposed) return;
                setStatus(error.status === 409 ? 'Конфликт версии — обновите состояние' : 'Не удалось сбросить настройки', 'error');
            }
        });

        listen(table, 'click', (event) => {
            const path = event.composedPath();
            if (path.find(node => node?.matches?.('sf-table')) !== table) return;
            const createControl = path.find((node) => node?.matches?.('sf-button[text="Создать"], sf-icon-button[icon="keyboard_arrow_down"]'));
            if (createControl) {
                const kind = createControl.matches('sf-button') ? 'create' : 'create-menu';
                table.dispatchEvent(new CustomEvent('sf-data-view-create-request', {
                    bubbles: true,
                    composed: true,
                    detail: {kind, view_key: state.view_key},
                }));
                setStatus(kind === 'create' ? 'Запрос создания передан backend-хосту' : 'Запрос вариантов создания передан backend-хосту', 'pending');
                return;
            }

        }, true);
        updatePagination(state.pagination || data.pagination || {page: 1, total: 0, pageSize: 10});
        if (contextIds !== null && !contextIds.length) setStatus('Выберите записи в связанном списке', 'empty');
        else setStatus(`Найдено: ${(state.pagination || data.pagination || {}).total || 0}`, 'success');
    };

    const bootDataview = async () => {
        await Promise.all([customElements.whenDefined('sf-table'), customElements.whenDefined('sf-pagination')]);
        const reconcile = () => {
            explicitHosts.forEach((registration, host) => {
                if (!host.isConnected || ownNode(host, 'sf-table') !== registration.table) registration.release();
            });
            const workbenches = new Set([...document.querySelectorAll(workbenchSelector), ...explicitHosts.keys()]);
            dataviewInstances.forEach((instance, workbench) => {
                if (!workbenches.has(workbench)
                    || instance.table !== ownNode(workbench, 'sf-table')
                    || instance.pagination !== ownNode(workbench, 'sf-pagination')
                    || instance.stateNode !== ownNode(workbench, '[data-larena-dataview-state]')) {
                    instance.dispose();
                    dataviewInstances.delete(workbench);
                }
            });
            workbenches.forEach((workbench) => initDataviewWorkbench(workbench));
        };
        reconcileDataview = reconcile;
        reconcile();
        const observer = new MutationObserver(reconcile);
        observer.observe(document.documentElement, {childList: true, subtree: true});
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bootDataview, {once: true});
    else bootDataview();
})();
