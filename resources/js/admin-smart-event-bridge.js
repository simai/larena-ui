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
    const deepMerge = (base, patch) => {
        const result = {...(base || {})};
        Object.entries(patch || {}).forEach(([key, value]) => {
            result[key] = value && typeof value === 'object' && !Array.isArray(value)
                ? deepMerge(result[key] || {}, value)
                : value;
        });
        return result;
    };

    const initDataviewWorkbench = async (workbench) => {
        const stateNode = workbench.querySelector('[data-larena-dataview-state]');
        const table = workbench.querySelector('sf-table');
        const pagination = workbench.querySelector('sf-pagination');
        const status = workbench.querySelector('[data-larena-dataview-status]');
        if (!stateNode || !table || !pagination) return;
        const state = JSON.parse(stateNode.textContent || '{}');
        let mode = 'user';
        let profiles = state.profiles || {};
        let queryState = state.query || {search: '', filters: {}, page: 1, page_size: 10};
        let querySequence = 0;

        const setStatus = (text, kind = 'ready') => {
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
        const runQuery = async (patch = {}) => {
            queryState = {...queryState, ...patch};
            const sequence = ++querySequence;
            setStatus('Загрузка…', 'loading');
            try {
                const payload = await request(state.query_endpoint, {method: 'POST', body: JSON.stringify({query: queryState})});
                if (sequence !== querySequence) return;
                queryState = payload.query;
                table.setRows?.(payload.rows || [], 'larena-query');
                updatePagination(payload.pagination);
                setStatus(payload.pagination.total ? `Найдено: ${payload.pagination.total}` : 'Ничего не найдено', payload.pagination.total ? 'success' : 'empty');
            } catch (error) {
                if (sequence !== querySequence) return;
                setStatus(error.status === 409 ? 'Конфликт версии — обновите состояние' : 'Ошибка загрузки. Черновик сохранён.', 'error');
            }
        };
        const saveProfile = async (profile) => {
            const target = mode === 'system' ? profiles.system : profiles.user;
            const endpointUrl = mode === 'system' ? state.system_endpoint : state.user_endpoint;
            setStatus('Сохранение…', 'saving');
            try {
                const payload = await request(endpointUrl, {
                    method: 'PUT',
                    body: JSON.stringify({base_revision: target?.revision || 0, profile}),
                });
                applyResponseProfiles(payload);
                setStatus('Настройки сохранены', 'success');
            } catch (error) {
                setStatus(error.status === 409 ? 'Конфликт версии — обновите состояние' : 'Не удалось сохранить. Черновик оставлен.', 'error');
            }
        };
        const currentProfile = () => JSON.parse(JSON.stringify(profileForMode()));

        table.addEventListener('onSearchEnd', (event) => runQuery({search: String(event.detail || ''), page: 1}));
        table.addEventListener('onFilterUpdate', (event) => runQuery({filters: event.detail?.values || {}, page: 1}));
        table.addEventListener('onTemplateSave', (event) => {
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
        table.addEventListener('onColumnSettingsChange', (event) => {
            const profile = currentProfile();
            profile.columns = event.detail?.columnSettings || {};
            saveProfile(profile);
        });
        pagination.addEventListener('sf-page-change', (event) => runQuery({page: event.detail?.current || 1}));
        pagination.addEventListener('sf-page-size-change', (event) => {
            const profile = currentProfile();
            profile.pagination = {page_size: event.detail?.pageSize || 10};
            saveProfile(profile);
            runQuery({page: 1, page_size: event.detail?.pageSize || 10});
        });
        pagination.addEventListener('sf-show-more', (event) => runQuery({page: Math.min((event.detail?.current || 1) + 1, event.detail?.pageCount || 1)}));
        pagination.addEventListener('sf-action-apply', (event) => {
            table.dispatchEvent(new CustomEvent('sf-data-view-bulk-action', {
                bubbles: true,
                composed: true,
                detail: event.detail || {},
            }));
            setStatus('Групповое демо-действие передано backend-хосту', 'success');
        });

        workbench.querySelectorAll('[data-larena-dataview-mode-button]').forEach((button) => {
            const targetMode = button.dataset.larenaDataviewModeButton;
            const allowed = targetMode !== 'system' || state.capabilities?.save_system === true;
            button.disabled = !allowed;
            button.addEventListener('click', () => {
                mode = targetMode;
                workbench.dataset.larenaDataviewMode = mode;
                workbench.querySelectorAll('[data-larena-dataview-mode-button]').forEach((candidate) => candidate.classList.toggle('larena-button-primary', candidate === button));
                applyProfile(profileForMode());
                setStatus(mode === 'system' ? 'Режим настроек для всех' : 'Режим личных настроек');
            });
        });
        workbench.querySelector('[data-larena-dataview-reset]')?.addEventListener('click', async () => {
            const target = mode === 'system' ? profiles.system : profiles.user;
            const endpointUrl = mode === 'system' ? state.system_endpoint : state.user_endpoint;
            setStatus('Сброс…', 'saving');
            try {
                const payload = await request(endpointUrl, {method: 'DELETE', body: JSON.stringify({base_revision: target?.revision || 0})});
                applyResponseProfiles(payload);
                setStatus('Настройки сброшены', 'success');
            } catch (error) {
                setStatus(error.status === 409 ? 'Конфликт версии — обновите состояние' : 'Не удалось сбросить настройки', 'error');
            }
        });

        table.addEventListener('click', (event) => {
            const path = event.composedPath();
            const createControl = path.find((node) => node?.matches?.('sf-button[text="Создать"], sf-icon-button[icon="keyboard_arrow_down"]'));
            if (createControl) {
                const kind = createControl.matches('sf-button') ? 'create' : 'create-menu';
                table.dispatchEvent(new CustomEvent('sf-data-view-create-request', {
                    bubbles: true,
                    composed: true,
                    detail: {kind, view_key: state.view_key},
                }));
                setStatus(kind === 'create' ? 'Запрос создания передан backend-хосту' : 'Запрос вариантов создания передан backend-хосту', 'success');
                return;
            }
            const rowControl = path.find((node) => node?.matches?.('sf-icon-button[value="edit"], sf-icon-button[value="view"], sf-icon-button[value="delete"]'));
            if (!rowControl) return;
            const cell = path.find((node) => node?.matches?.('td[data-item]'));
            const rowId = String(cell?.dataset?.item || '').split('_')[0] || null;
            const action = rowControl.getAttribute('value') || '';
            table.dispatchEvent(new CustomEvent('sf-data-view-row-action', {
                bubbles: true,
                composed: true,
                detail: {action, row_id: rowId, view_key: state.view_key},
            }));
            setStatus(`Демо-действие «${action}» передано backend-хосту${rowId ? ` для #${rowId}` : ''}`, 'success');
        }, true);
        updatePagination(state.pagination || data.pagination || {page: 1, total: 0, pageSize: 10});
        setStatus(`Найдено: ${(state.pagination || data.pagination || {}).total || 0}`, 'success');
    };

    const bootDataview = async () => {
        const workbenches = document.querySelectorAll(workbenchSelector);
        if (!workbenches.length) return;
        await Promise.all([customElements.whenDefined('sf-table'), customElements.whenDefined('sf-pagination')]);
        workbenches.forEach((workbench) => initDataviewWorkbench(workbench));
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bootDataview, {once: true});
    else bootDataview();
})();
