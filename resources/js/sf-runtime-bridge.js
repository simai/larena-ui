(function () {
  'use strict';

  function connectTablePreferences(target, form, lifecycle) {
    if (!form?.dataset?.larenaTablePreferences) return;
    var state = JSON.parse(form.dataset.larenaTablePreferences);
    if (!state || !Number.isInteger(state.revision)) return;
    target.setTableSettings({columnSettings: state.columns || {}}, 'larena-preferences-load');
    // Column changes go where the page says: into the open saved view (with that view's revision)
    // or into the person's own layer.
    var settingsTarget = state.target && state.target.kind === 'saved_view' ? state.target : null;
    var revision = settingsTarget ? settingsTarget.revision : state.revision;
    var pending = Promise.resolve();
    var failed = false;
    var inFlight = 0;
    var status = form.querySelector('[data-larena-preferences-status]');
    var ru = document.documentElement.lang.startsWith('ru');
    function notice(message) {
      if (!status) return;
      status.hidden = false;
      status.textContent = message;
    }
    function offerRefresh() {
      if (!status || lifecycle.signal.aborted) return;
      var refresh = document.createElement('button');
      refresh.type = 'button';
      refresh.textContent = ru ? 'Обновить ревизию' : 'Refresh revision';
      status.replaceChildren(document.createTextNode(ru
        ? 'Настройки изменились в другом окне. Ваш выбор остаётся на экране. '
        : 'Settings changed elsewhere. Your draft remains on screen. '), refresh);
      refresh.addEventListener('click', async function () {
        refresh.disabled = true;
        try {
          var response = await fetch(window.location.href, {credentials: 'same-origin',
            headers: {Accept: 'application/json'}, signal: lifecycle.signal});
          var fresh = await response.json();
          var layer = settingsTarget ? fresh.table_preferences?.target : fresh.table_preferences?.personal;
          if (!response.ok || fresh.status !== 'ok' || !Number.isInteger(layer?.revision)
            || (settingsTarget && (layer?.kind !== 'saved_view'
              || layer?.saved_view_id !== settingsTarget.saved_view_id))) throw new Error('preferences-refresh-rejected');
          revision = layer.revision;
          var retry = document.createElement('button');
          retry.type = 'button';
          retry.textContent = ru ? 'Сохранить мой вариант' : 'Save my draft';
          status.replaceChildren(document.createTextNode(ru
            ? 'Ревизия обновлена. Ваш вариант остаётся на экране. '
            : 'Revision refreshed. Your draft remains on screen. '), retry);
          retry.addEventListener('click', function () {
            failed = false;
            target.dispatchEvent(new CustomEvent('sf-table:column-settings-change',
              {detail: {columnSettings: latest}}));
          }, {once: true, signal: lifecycle.signal});
        } catch (error) {
          if (!lifecycle.signal.aborted) {
            refresh.disabled = false;
            status.firstChild.textContent = ru
              ? 'Не удалось обновить ревизию. Ваш вариант остаётся на экране. '
              : 'Could not refresh the revision. Your draft remains on screen. ';
          }
        }
      }, {signal: lifecycle.signal});
    }
    window.addEventListener('beforeunload', function (event) {
      if (inFlight) { event.preventDefault(); event.returnValue = ''; }
    }, {signal: lifecycle.signal});
    // Only field columns are settings; the row actions column also reports a width.
    function fieldColumns(source) {
      var columns = JSON.parse(JSON.stringify(source || {}));
      var keys = form.dataset.larenaPreferenceKeys ? JSON.parse(form.dataset.larenaPreferenceKeys) : null;
      if (Array.isArray(keys)) {
        Object.keys(columns).forEach(function (key) { if (keys.indexOf(key) === -1) delete columns[key]; });
      }
      return columns;
    }
    var latest = fieldColumns(state.columns);
    connectSharedColumns(target, form, lifecycle, function () { return latest; });
    target.addEventListener('sf-table:column-settings-change', function (event) {
      if (event.target !== target || lifecycle.signal.aborted) return;
      var columns = fieldColumns(event.detail?.columnSettings);
      latest = columns;
      if (failed) return;
      inFlight += 1;
      notice(ru ? 'Сохраняем настройки столбцов…' : 'Saving column settings…');
      // Serialize changes so a resize followed by hide cannot overwrite a newer revision.
      pending = pending.then(async function () {
        if (failed) return;
        var response = await fetch(form.dataset.larenaPreferencesUrl, {
          method: 'POST', credentials: 'same-origin',
          headers: {'Content-Type': 'application/json', Accept: 'application/json',
            'X-CSRF-TOKEN': form.dataset.larenaPreferencesCsrf},
          body: JSON.stringify(settingsTarget
            ? {action: 'dataview.saved_view.layout',
              scope_ref: form.querySelector('[name="scope_ref"]').value,
              payload: {saved_view_id: settingsTarget.saved_view_id, base_revision: revision, columns: columns}}
            : {action: 'dataview.preferences.save',
              scope_ref: form.querySelector('[name="scope_ref"]').value,
              payload: {structure_id: form.querySelector('[name="structure_id"]').value,
                base_revision: revision, columns: columns}})
        });
        var receipt = await response.json();
        if (!response.ok || receipt.status !== 'ok') throw new Error(
          receipt.reason_code === 'minimal_cms_revision_conflict' ? 'preferences-conflict' : 'preferences-save-rejected');
        revision += 1;
      }).catch(function (error) {
        failed = true;
        if (error.message === 'preferences-conflict') offerRefresh();
      }).finally(function () {
        inFlight -= 1;
        if (failed) {
          if (!status?.querySelector('button')) notice(ru
            ? 'Настройки не сохранены. Перезагрузите страницу и повторите изменение.'
            : 'Settings were not saved. Reload the page and try the change again.');
        } else if (!inFlight) notice(ru ? 'Настройки столбцов сохранены.' : 'Column settings saved.');
      });
    });
  }

  // Column settings for everyone are saved only when a person asks for it, and only if the server
  // still holds the shared revision this page was rendered with.
  function connectSharedColumns(target, form, lifecycle, columns) {
    var panel = document.querySelector('[data-larena-shared-settings]');
    var button = panel?.querySelector('#minimal-cms-share-columns');
    if (!panel || !button) return;
    var status = form.querySelector('[data-larena-preferences-status]');
    var revision = Number.parseInt(panel.dataset.larenaSharedRevision || '0', 10);
    if (!Number.isInteger(revision)) return;
    function offerSharedRefresh() {
      if (!status) return;
      var ru = document.documentElement.lang.startsWith('ru');
      var refresh = document.createElement('button');
      refresh.type = 'button';
      refresh.textContent = ru ? 'Обновить общую ревизию' : 'Refresh shared revision';
      status.replaceChildren(document.createTextNode(ru
        ? 'Общие настройки изменились. Ваш выбор остаётся на экране. '
        : 'Shared settings changed. Your draft remains on screen. '), refresh);
      refresh.addEventListener('click', async function () {
        refresh.disabled = true;
        try {
          var response = await fetch(window.location.href, {credentials: 'same-origin',
            headers: {Accept: 'application/json'}, signal: lifecycle.signal});
          var fresh = await response.json();
          if (!response.ok || fresh.status !== 'ok'
            || !Number.isInteger(fresh.table_preferences?.shared?.revision)) throw new Error('shared-refresh-rejected');
          revision = fresh.table_preferences.shared.revision;
          var retry = document.createElement('button');
          retry.type = 'button';
          retry.textContent = ru ? 'Применить мой вариант для всех' : 'Apply my draft for everyone';
          status.replaceChildren(document.createTextNode(ru
            ? 'Общая ревизия обновлена. Ваш вариант остаётся на экране. '
            : 'Shared revision refreshed. Your draft remains on screen. '), retry);
          retry.addEventListener('click', function () { button.click(); }, {once: true, signal: lifecycle.signal});
        } catch (error) {
          if (!lifecycle.signal.aborted) {
            refresh.disabled = false;
            status.firstChild.textContent = ru
              ? 'Не удалось обновить общую ревизию. Ваш вариант остаётся на экране. '
              : 'Could not refresh the shared revision. Your draft remains on screen. ';
          }
        }
      }, {signal: lifecycle.signal});
    }
    button.addEventListener('click', async function () {
      if (lifecycle.signal.aborted) return;
      button.setAttribute('disabled', '');
      try {
        var response = await fetch(form.dataset.larenaPreferencesUrl, {
          method: 'POST', credentials: 'same-origin', signal: lifecycle.signal,
          headers: {'Content-Type': 'application/json', Accept: 'application/json',
            'X-CSRF-TOKEN': form.dataset.larenaPreferencesCsrf},
          body: JSON.stringify({action: 'dataview.preferences.save_shared',
            scope_ref: form.querySelector('[name="scope_ref"]').value,
            payload: {structure_id: form.querySelector('[name="structure_id"]').value,
              base_revision: revision, columns: columns()}})
        });
        var receipt = await response.json();
        if (!response.ok || receipt.status !== 'ok') throw new Error(
          receipt.reason_code === 'minimal_cms_revision_conflict' ? 'shared-preferences-conflict' : 'shared-preferences-rejected');
        revision += 1;
        if (status) { status.hidden = false; status.textContent = panel.dataset.larenaSharedSaved || ''; }
      } catch (error) {
        if (lifecycle.signal.aborted) return;
        if (status) {
          status.hidden = false;
          if (error.message === 'shared-preferences-conflict') offerSharedRefresh();
          else status.textContent = panel.dataset.larenaSharedFailed || '';
        }
      } finally {
        button.removeAttribute('disabled');
      }
    }, {signal: lifecycle.signal});
  }

  function connectPagination(pagination, form, target, lifecycle, appendRows) {
    if (!form?.dataset?.larenaPaginationQuery) return;
    var query = JSON.parse(form.dataset.larenaPaginationQuery);
    if (!query || !query.structure_id || !query.scope_ref) return;
    function navigate(page, size) {
      if (!Number.isInteger(page) || page < 1 || page > 100000) return;
      if (![10, 20, 50, 100].includes(size)) return;
      var url = new URL(form.action, window.location.href);
      Object.entries(query).forEach(function (entry) {
        var key = entry[0], value = entry[1];
        if (value === null || value === undefined || value === '' || value === false) return;
        url.searchParams.set(key, value === true ? '1' : String(value));
      });
      url.searchParams.set('page', String(page));
      url.searchParams.set('per_page', String(size));
      window.location.assign(url.href);
    }
    pagination.addEventListener('sf-page-change', function (event) {
      if (event.target !== pagination) return;
      navigate(event.detail?.current, query.per_page || 20);
    }, {signal: lifecycle.signal});
    pagination.addEventListener('sf-page-size-change', function (event) {
      if (event.target !== pagination) return;
      navigate(1, event.detail?.pageSize);
    }, {signal: lifecycle.signal});
    var fallback = pagination.querySelector('[data-larena-pagination-next]');
    var nextHref = fallback?.getAttribute('href') || null;
    if (fallback) fallback.remove();
    var loading = false;
    var status = form.querySelector('[data-larena-pagination-status]');
    var ru = document.documentElement.lang.startsWith('ru');
    pagination.addEventListener('sf-show-more', async function (event) {
      if (event.target !== pagination || loading || !nextHref) return;
      loading = true;
      // Every request of this instance is numbered; a response that is no longer the newest,
      // or that belongs to a disposed instance, is dropped instead of being applied.
      var issued = lifecycle.next();
      if (status) { status.hidden = false; status.textContent = ru ? 'Загружаем записи…' : 'Loading records…'; }
      try {
        var url = new URL(nextHref, window.location.href);
        if (url.origin !== window.location.origin) throw new Error('pagination-origin');
        var response = await fetch(url.href, {credentials: 'same-origin', headers: {Accept: 'text/html'}, signal: lifecycle.signal});
        if (!response.ok) throw new Error('pagination-response');
        if (!lifecycle.current(issued)) return;
        var html = await response.text();
        if (html.length > 2097152) throw new Error('pagination-size');
        // Parse the backend's existing JSON hydration contract; never insert its HTML or execute scripts.
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var nextForm = doc.querySelector('[data-larena-dataview-query]');
        var nextQuery = JSON.parse(nextForm?.dataset?.larenaPaginationQuery || 'null');
        if (!nextQuery || nextQuery.scope_ref !== query.scope_ref || nextQuery.structure_id !== query.structure_id)
          throw new Error('pagination-scope');
        var node = Array.from(nextForm.querySelectorAll('script[data-larena-smart-hydration]')).find(function (item) {
          return JSON.parse(item.textContent || '{}').component === 'sf-table';
        });
        var descriptor = JSON.parse(node?.textContent || 'null');
        if (!descriptor || !Array.isArray(descriptor.props?.data?.rows)) throw new Error('pagination-data');
        if (!lifecycle.current(issued)) return;
        appendRows(descriptor.props.data.rows);
        nextHref = nextForm.querySelector('[data-larena-pagination-next]')?.getAttribute('href') || null;
        pagination.setAttribute('current', String(nextQuery.page));
        pagination.setAttribute('total', String(descriptor.props.data.pagination.total));
        if (status) status.textContent = ru ? 'Записи загружены.' : 'Records loaded.';
      } catch (error) {
        if (lifecycle.signal.aborted) return;
        if (status) status.textContent = ru ? 'Не удалось загрузить записи. Повторите попытку.' : 'Could not load records. Try again.';
      } finally { loading = false; }
    }, {signal: lifecycle.signal});
    return {clearContinuation: function () { nextHref = null; }};
  }

  function connectCmsPort(target, form, lifecycle, acceptRows, pagination, paginationPort) {
    if (!form?.dataset?.larenaPortUrl?.startsWith('/')) return;
    var scope = form.querySelector('[name="scope_ref"]')?.value;
    var structure = form.querySelector('[name="structure_id"]')?.value;
    if (!scope || !structure) return;
    var status = form.querySelector('[data-larena-port-status]');
    var records = form.closest('[data-larena-minimal-cms]')?.querySelector('[data-larena-row-revisions]');
    var labels = {};
    try { labels = JSON.parse(form.dataset.larenaPortActionLabels || '{}'); } catch { labels = {}; }
    var ru = document.documentElement.lang.startsWith('ru');
    var latest = 0;
    function notice(kind) {
      if (!status || lifecycle.signal.aborted) return;
      status.hidden = false;
      status.dataset.state = kind;
      status.textContent = kind === 'loading' ? (ru ? 'Загружаем записи…' : 'Loading records…')
        : kind === 'empty' ? (ru ? 'Записей нет.' : 'No records.')
          : kind === 'error' ? (ru ? 'Не удалось загрузить записи. Предыдущий список сохранён.' : 'Could not load records. Previous records remain visible.')
            : (ru ? 'Записи загружены.' : 'Records loaded.');
    }
    function display(value) {
      if (value === null || value === undefined) return '';
      if (typeof value === 'string') return value;
      if (typeof value === 'number' || typeof value === 'boolean') return String(value);
      return JSON.stringify(value);
    }
    function tableRows(data) {
      var columns = Array.isArray(data.columns) ? data.columns.map(function (column) { return column.key; }) : [];
      var actionTypes = {
        'record.edit': ['edit', 'larena.record.edit', labels.edit || 'Edit'],
        'record.view': ['visibility', 'larena.record.view', labels.view || 'View'],
        'record.delete': ['delete', 'larena.record.delete', labels.delete || 'Delete'],
        'record.restore': ['restore', 'larena.record.restore', labels.restore || 'Restore'],
      };
      return data.records.map(function (record) {
        var row = {id: record.id};
        columns.forEach(function (key) { row[key] = display(record.values?.[key]); });
        row.actions = (record.actions || []).filter(function (action) { return actionTypes[action]; }).map(function (action) {
          var type = actionTypes[action];
          return {component: {type: 'icon-button', props: {variant: 'icon', type: 'link', scheme: 'on-surface', size: '1',
            icon: type[0], value: type[1], ariaLabel: type[2]}}};
        });
        return row;
      });
    }
    target.addEventListener('sf-table-query-intent', async function (event) {
      if (event.target !== target || lifecycle.signal.aborted || event.detail?.reason !== 'context') return;
      var sequence = event.detail?.sequence;
      var ids = event.detail?.context?.record_ids;
      if (!Number.isSafeInteger(sequence) || sequence < 1 || !Array.isArray(ids)) return;
      latest = sequence;
      notice('loading');
      try {
        var response = await fetch(form.dataset.larenaPortUrl, {
          method: 'POST', credentials: 'same-origin', signal: lifecycle.signal,
          headers: {'Content-Type': 'application/json', Accept: 'application/json',
            'X-CSRF-TOKEN': form.dataset.larenaPreferencesCsrf || ''},
          body: JSON.stringify({scope_ref: scope, structure_id: structure, intent: 'query.change',
            payload: {sequence: sequence, query: {record_ids: ids}}}),
        });
        var answer = await response.json();
        if (lifecycle.signal.aborted || sequence !== latest) return;
        if (!response.ok || answer.answer !== 'applied' || answer.sequence !== sequence
          || !Array.isArray(answer.data?.records) || !Array.isArray(answer.data?.columns)
          || !Number.isInteger(answer.data?.total)) throw new Error('port_query_unavailable');
        var projected = tableRows(answer.data);
        var normalized = normalizeTableRows(target, projected);
        if (!target.applyQueryResult?.(sequence, normalized)) return;
        acceptRows(projected, normalized);
        paginationPort?.clearContinuation();
        if (pagination) {
          pagination.setAttribute('current', '1');
          pagination.setAttribute('total', String(answer.data.total));
          pagination.setAttribute('selection-total', String(normalized.length));
          pagination.setAttribute('selected-count', '0');
          pagination.removeAttribute('show-action-for-all');
        }
        if (records) {
          records.dataset.larenaRowRevisions = JSON.stringify(Object.fromEntries(answer.data.records.map(function (record) {
            return [record.id, record.revision];
          })));
          records.dataset.larenaMatchedCount = String(answer.data.total);
          records.dataset.larenaBulkAll = '';
        }
        notice(normalized.length ? 'populated' : 'empty');
      } catch (error) {
        if (lifecycle.signal.aborted || sequence !== latest) return;
        target.setDataState?.('error');
        notice('error');
      }
    }, {signal: lifecycle.signal});
  }

  function normalizeTableRows(target, rows) {
    return (rows || []).map(function (row) {
        var wire = function (action) {
          var intent = action.component?.props?.value;
          if (intent !== 'larena.record.edit' && intent !== 'larena.record.view'
            && intent !== 'larena.record.delete' && intent !== 'larena.record.restore') return action;
          return Object.assign({}, action, {component: Object.assign({}, action.component, {
            props: Object.assign({}, action.component.props, {'@click': function (event) {
              event.preventDefault();
              event.stopPropagation();
              var url = new URL(window.location.href);
              url.searchParams.set('record_id', String(row.id));
              var operation = intent.replace('larena.record.', '');
              var mode = operation === 'restore' ? 'delete' : operation;
              url.searchParams.set('record_mode', mode);
              url.hash = mode === 'view' ? 'minimal-cms-record-view' : 'minimal-cms-record-editor';
              window.location.assign(url.href);
            }})
          })});
        };
        var actions = (row.actions || []).map(wire);
        var normalized = Object.assign({}, row, {actions: actions});
        // A cell may carry the same record intent, such as the linked record name.
        Object.keys(normalized).forEach(function (key) {
          var cell = normalized[key];
          if (key !== 'actions' && cell && typeof cell === 'object' && !Array.isArray(cell) && cell.component) {
            normalized[key] = wire(cell);
          }
        });
        if (actions.length && typeof target.getDefaultRowCell === 'function') {
          var settings = target.getDefaultRowCell({key: 'settings'}, normalized);
          settings.component.props['@click'] = function (event) {
            target.openContextMenu(event, {type: 'left', menu: 'row-settings', row: normalized,
              items: actions.filter(function (action) {
                return typeof action.component?.props?.['@click'] === 'function';
              }).map(function (action) {
                var props = action.component.props;
                return {component: 'sf-button', props: {type: 'link', scheme: 'on-surface',
                  text: props.ariaLabel, iconLeft: props.icon,
                  '@click': function (click) {
                    target.closeContextMenu(false);
                    props['@click'](click);
                  }}};
              })});
          };
          normalized.settings = settings;
        }
        return normalized;
      });
  }

  // One lifecycle per hydrated instance: every listener, observer and request belongs to it, and
  // disposing the instance releases all of them without touching any other instance on the page.
  function instanceLifecycle(target) {
    if (target.larenaLifecycle) return target.larenaLifecycle;
    var controller = new AbortController();
    var observers = [];
    var lifecycle = {
      signal: controller.signal,
      sequence: 0,
      next: function () { lifecycle.sequence += 1; return lifecycle.sequence; },
      current: function (issued) { return issued === lifecycle.sequence && !controller.signal.aborted; },
      observe: function (observer) { observers.push(observer); return observer; },
      dispose: function () {
        if (controller.signal.aborted) return;
        controller.abort();
        observers.forEach(function (observer) { observer.disconnect(); });
        observers.length = 0;
        delete target.larenaLifecycle;
      },
    };
    target.larenaLifecycle = lifecycle;
    lifecycle.observe(new MutationObserver(function () {
      if (!target.isConnected) lifecycle.dispose();
    })).observe(document.documentElement, {childList: true, subtree: true});
    return lifecycle;
  }

  function hydrate(descriptor) {
    var target = document.getElementById(descriptor.target);
    if (!target || descriptor.component !== target.localName) {
      throw new Error('larena-smart-hydration-target-mismatch');
    }
    var lifecycle = instanceLifecycle(target);
    if (descriptor.component === 'sf-table') {
      if (typeof target.setTableData !== 'function') {
        throw new Error('larena-smart-table-api-unavailable');
      }
      var data = descriptor.props.data || {};
      var rows = normalizeTableRows(target, data.rows);
      target.setTableData(Object.assign({}, data, {rows: []}), 'larena-backend-hydration');
      // setRows normalizes system cells and binds their native row handlers.
      // Supplying raw rows through setTableData leaves the menu button inert.
      if (typeof target.setRows !== 'function') {
        throw new Error('larena-smart-table-rows-api-unavailable');
      }
      target.setRows(rows, 'larena-backend-hydration');
      // A slotted table belongs to sf-data-view. Its rows and listeners come from the
      // host port, never from the legacy single-table query and pagination bridge.
      if (target.closest('sf-data-view')) {
        target.setAttribute('data-larena-hydrated', 'true');
        return;
      }
      var workbench = target.closest('[data-larena-dataview-workbench]');
      // The Dataview workbench holds its query form beside the table; the CMS list wraps the table in it.
      var queryForm = (workbench && workbench.querySelector('[data-larena-dataview-query]'))
        || target.closest('form[data-larena-dataview-query]');
      connectTablePreferences(target, queryForm, lifecycle);
      var pagination = (workbench || queryForm) && (workbench || queryForm).querySelector('sf-pagination');
      var paginationPort = null;
      if (pagination) {
        var syncPaginationSelection = function () {
          var selected = target.querySelectorAll('tbody td[data-key="select"] input[type="checkbox"][value]:checked').length;
          pagination.setAttribute('selected-count', String(selected));
          pagination.setAttribute('selection-total', String(rows.length));
        };
        paginationPort = connectPagination(pagination, queryForm, target, lifecycle, function (incoming) {
          var ids = new Set((data.rows || []).map(function (row) { return String(row.id); }));
          data.rows = (data.rows || []).concat(incoming.filter(function (row) {
            if (ids.has(String(row.id))) return false;
            ids.add(String(row.id)); return true;
          }));
          rows = normalizeTableRows(target, data.rows);
          target.setRows(rows, 'larena-page-append');
          pagination.setAttribute('selection-total', String(rows.length));
        });
        pagination.setAttribute('selected-count', '0');
        pagination.setAttribute('selection-total', String(rows.length));
        // Smart Table owns row checkbox changes internally and rerenders their
        // checked attributes without publishing a composed selection event.
        // Observe that rendered projection so async and appended rows remain
        // reflected in the adjacent pagination component.
        var selectionSyncTimer = null;
        lifecycle.observe(new MutationObserver(function (mutations) {
          if (mutations.some(function (mutation) {
            return mutation.type === 'childList' || mutation.attributeName === 'checked';
          })) {
            clearTimeout(selectionSyncTimer);
            selectionSyncTimer = setTimeout(syncPaginationSelection, 0);
          }
        })).observe(target, {subtree: true, childList: true, attributes: true, attributeFilter: ['checked']});
        target.addEventListener('sf-table-selection-change', function (event) {
          if (event.target !== target) return;
          var detail = event.detail || {};
          if (!Array.isArray(detail.selectedIds) || !Number.isInteger(detail.selectedCount)
            || detail.selectedCount < 0 || detail.selectedCount > rows.length
            || detail.selectedIds.length !== detail.selectedCount
            || detail.rowCount !== rows.length) return;
          pagination.setAttribute('selected-count', String(detail.selectedCount));
          pagination.setAttribute('selection-total', String(rows.length));
        }, {signal: lifecycle.signal});
      }
      connectCmsPort(target, queryForm, lifecycle, function (projected, normalized) {
        data.rows = projected;
        rows = normalized;
      }, pagination, paginationPort);
    }
    target.setAttribute('data-larena-hydrated', 'true');
  }

  function applyNativeInputAttributes(target) {
    if (!target || target.localName !== 'sf-input') return;
    var input = target.querySelector('input');
    if (!input) return;
    ['autocomplete'].forEach(function (attribute) {
      if (target.hasAttribute(attribute)) {
        input.setAttribute(attribute, target.getAttribute(attribute) || '');
      } else {
        input.removeAttribute(attribute);
      }
    });
  }

  function syncNativeInputAttributes(root) {
    var scope = root && typeof root.querySelectorAll === 'function' ? root : document;
    if (root && root.localName === 'sf-input') applyNativeInputAttributes(root);
    if (root && typeof root.closest === 'function') applyNativeInputAttributes(root.closest('sf-input'));
    scope.querySelectorAll('sf-input').forEach(applyNativeInputAttributes);
  }

  function applyReadOnlyTable(target) {
    if (!target || target.localName !== 'sf-table' || !target.closest('[data-larena-read-only="true"]')) return;
    ['selectable', 'settings', 'actions'].forEach(function (attribute) {
      if (target.getAttribute(attribute) !== 'false') target.setAttribute(attribute, 'false');
    });
    var toolbar = target.querySelector('.sf-table-toolbar');
    if (toolbar) toolbar.remove();
  }

  function syncReadOnlyTables(root) {
    var scope = root && typeof root.querySelectorAll === 'function' ? root : document;
    if (root && root.localName === 'sf-table') applyReadOnlyTable(root);
    if (root && typeof root.closest === 'function') applyReadOnlyTable(root.closest('sf-table'));
    scope.querySelectorAll('[data-larena-read-only="true"] sf-table').forEach(applyReadOnlyTable);
  }

  function connectCmsComposite(view) {
    var form = view.closest('form[data-larena-dataview-query]');
    var records = view.closest('[data-larena-row-revisions]');
    if (!form || !records || !form.dataset.larenaPortUrl?.startsWith('/')) return;
    var storageWorkbench = records.dataset.larenaStorageWorkbenchComposite === '1';
    var scope = form.querySelector('[name="scope_ref"]')?.value;
    var structure = form.querySelector('[name="structure_id"]')?.value;
    if (!scope || !structure || view.dataset.larenaPortConnected === 'true') return;
    var endpoint = new URL(form.dataset.larenaPortUrl, window.location.href);
    if (endpoint.origin !== window.location.origin) return;
    var field = function (name) { return form.elements.namedItem(name); };
    var parse = function (value, fallback) {
      try { return JSON.parse(value); } catch { return fallback; }
    };
    var page = parse(form.dataset.larenaPaginationQuery || 'null', {}) || {};
    var sortField = field('sort_field')?.value || page.sort_field || '';
    var chosenFilters = parse(field('filters')?.value || page.filters || '{}', {});
    var current = {
      filters: chosenFilters, search: field('search')?.value || page.search || '',
      sort: sortField ? [{key: sortField, direction: (field('sort_direction')?.value || page.sort_direction) === 'desc' ? 'desc' : 'asc'}] : [],
      page: Number(page.page) || 1, per_page: Number(page.per_page) || 20,
    };
    if (field('dataview_id')?.value) current.dataview_id = field('dataview_id').value;
    if (field('include_deleted')?.value === '1') current.include_deleted = true;
    if (storageWorkbench && field('include_archived')?.value === '1') current.include_archived = true;
    if (field('loaded_saved_view_id')?.value) current.saved_view_id = field('loaded_saved_view_id').value;
    view.query = current;
    var table = view.table;
    if (view.pagination && records.dataset.larenaBulkAll === '1') {
      view.pagination.setAttribute('show-action-for-all', '');
    }
    if (table && typeof table.setFilterFields === 'function') {
      var fields = parse(records.dataset.larenaFilterFields || '[]', []);
      if (Array.isArray(fields)) table.setFilterFields(fields);
    }
    var actions = {'record.view': 'view', 'record.edit': 'edit', 'record.delete': 'delete',
      'record.restore': 'restore'};
    var latestQuerySequence = null;
    var syncMatchedCount = function (total) {
      var count = String(total);
      records.dataset.larenaMatchedCount = count;
      if (storageWorkbench) {
        var heading = records.closest?.('.larena-workbench-records')?.querySelector('.larena-workbench-section-header p');
        if (heading && /\d[\d\s.,]*\s*$/.test(heading.textContent || '')) {
          heading.textContent = heading.textContent.replace(/\d[\d\s.,]*\s*$/, count);
        }
      }
      var badge = document.getElementById('minimal-cms-record-count');
      if (!badge) return;
      var oldCount = badge.getAttribute('text');
      var label = badge.getAttribute('aria-label');
      if (oldCount && label?.endsWith(oldCount)) {
        badge.setAttribute('aria-label', label.slice(0, -oldCount.length) + count);
      }
      badge.setAttribute('text', count);
      var visibleText = badge.querySelector('.sf-badge-text');
      if (visibleText) visibleText.textContent = count;
    };
    var projectRecord = function (record, keys) {
      // The host supplies Property/owner-projected display cells. Raw reference IDs
      // in record.values are never placed into the table DOM.
      if (!record || !record.display_values || Array.isArray(record.display_values)) {
        throw new Error('port_display_projection_missing');
      }
      var row = {id: record.id, revision: record.revision,
        actions: storageWorkbench ? [] : (record.actions || []).map(function (action) { return actions[action]; }).filter(Boolean)};
      keys.forEach(function (key) { row[key] = record.display_values[key] ?? null; });
      return row;
    };
    var project = function (data) {
      if (!Array.isArray(data?.records) || !Array.isArray(data?.columns) || !Number.isSafeInteger(data?.total)) {
        throw new Error('port_projection_invalid');
      }
      var keys = data.columns.map(function (column) { return column.key; });
      var projected = data.records.map(function (record) { return projectRecord(record, keys); });
      records.dataset.larenaRowRevisions = JSON.stringify(Object.fromEntries(data.records.map(function (record) {
        return [record.id, record.revision];
      })));
      syncMatchedCount(data.total);
      if (storageWorkbench) {
        var legacy = records.closest?.('.larena-workbench-records')?.querySelector('[data-larena-storage-legacy]');
        if (legacy) legacy.open = false;
      }
      return {...data, records: projected};
    };
    var navigateRecord = function (id, mode) {
      var url = new URL(window.location.href);
      if (id) url.searchParams.set('record_id', id);
      else url.searchParams.delete('record_id');
      url.searchParams.set('record_mode', mode);
      url.hash = mode === 'view' ? 'minimal-cms-record-view' : 'minimal-cms-record-editor';
      // The record panel opens over the list in place when the admin page offers it.
      if (window.LarenaRecordPanel && window.LarenaRecordPanel.open(url.href)) return;
      window.location.assign(url.href);
    };
    var permissions = parse(records.dataset.larenaPortCapabilities || '[]', []);
    var shownRecords = [];
    // The query of the shown rows; "Show more" appends the next pages of the same query.
    var listQuery = current;
    var post = async function (intent, sent, signal) {
      var response = await fetch(endpoint.href, {method: 'POST', credentials: 'same-origin', signal: signal,
        headers: {'Content-Type': 'application/json', Accept: 'application/json',
          'X-CSRF-TOKEN': form.dataset.larenaPreferencesCsrf || ''},
        body: JSON.stringify({scope_ref: scope, structure_id: structure, intent: intent, payload: sent})});
      var answer = await response.json();
      if (!['applied', 'conflict', 'refused', 'unavailable'].includes(answer?.answer)) {
        throw new Error('port_answer_invalid');
      }
      return answer;
    };
    // After the record panel saved a record, only that row is written again: the page that holds
    // it is asked once and the row is patched into the table (updateRecord). A record the shown
    // rows do not hold (a new one, or one that left the filter) asks the table for its page again.
    view.larenaRefreshRecord = async function (id) {
      var key = String(id || '');
      var index = shownRecords.findIndex(function (row) { return String(row.id) === key; });
      if (!key || index < 0) { view.refresh?.('record-unknown'); return false; }
      var perPage = Number(listQuery?.per_page) || 20;
      var asked = {...listQuery, page: (Number(listQuery?.page) || 1) + Math.floor(index / perPage)};
      try {
        var answer = await post('query.change', {sequence: Number.MAX_SAFE_INTEGER, query: asked});
        var columns = Array.isArray(answer?.data?.columns) ? answer.data.columns.map(function (column) { return column.key; }) : null;
        var record = columns && Array.isArray(answer.data.records)
          ? answer.data.records.find(function (item) { return String(item?.id) === key; }) : null;
        if (answer.answer !== 'applied' || !record) { view.refresh?.('record-unknown'); return false; }
        var row = projectRecord(record, columns);
        if (Number.isSafeInteger(answer.data.total)) syncMatchedCount(answer.data.total);
        shownRecords[index] = row;
        records.dataset.larenaRowRevisions = JSON.stringify(Object.fromEntries(shownRecords.map(function (item) {
          return [item.id, item.revision];
        })));
        return view.updateRecord?.(row.id, row) === true;
      } catch {
        view.refresh?.('record-unknown');
        return false;
      }
    };
    view.setHostPort({version: '1.4.0', capabilities: Array.isArray(permissions) ? permissions : [],
    raise: async function (intent, payload, options) {
      if (intent === 'query.change') latestQuerySequence = payload?.sequence;
      if ((intent === 'record.mutate' && payload?.action_id === 'delete')
        || intent === 'bulk.apply_selected' || intent === 'bulk.apply_under_filter') {
        if (!window.confirm(records.dataset.larenaConfirmBulkDelete || 'Confirm this action?')) {
          return {answer: 'refused', reason: 'cancelled'};
        }
      }
      var sent = {...payload};
      if (intent === 'view.save') {
        sent = {key: payload?.key, query: payload?.query || {}};
        if (Number.isInteger(payload?.revision)) sent.revision = payload.revision;
        for (var property of ['roles', 'layout', 'settings']) {
          if (payload && Object.hasOwn(payload, property)) sent[property] = payload[property];
        }
      }
      if (intent === 'bulk.apply_selected' || intent === 'bulk.apply_under_filter') {
        if (sent.action_id === 'bulk_delete') sent.action_id = 'archive';
        if (storageWorkbench && intent === 'bulk.apply_selected') sent.confirmed = true;
      }
      try {
        var answer = await post(intent, sent, options?.signal);
        // The composite drops an obsolete reply after raise resolves; host-owned counters
        // and row revisions must wait for that same newest query.
        if (answer.answer === 'applied' && answer.data
          && (intent !== 'query.change' || payload?.sequence === latestQuerySequence)) {
          answer.data = project(answer.data);
          if (intent === 'query.change') {
            // The data view adds the rows of "Show more" under the shown ones; the revisions of
            // every shown row stay known, so a bulk action on earlier rows still checks them.
            shownRecords = payload?.reason === 'show-more' ? shownRecords.concat(answer.data.records) : answer.data.records;
            if (payload?.reason !== 'show-more' && payload?.query) listQuery = payload.query;
            records.dataset.larenaRowRevisions = JSON.stringify(Object.fromEntries(shownRecords.map(function (row) {
              return [row.id, row.revision];
            })));
          }
        }
        if (answer.answer === 'applied' && intent === 'record.open') navigateRecord(payload.record_ids[0], 'view');
        if (answer.answer === 'applied' && intent === 'record.mutate' && payload.action_id === 'edit') {
          navigateRecord(payload.record_ids[0], 'edit');
        }
        if (answer.answer === 'applied' && intent === 'view.create_record') navigateRecord('', 'create');
        return answer;
      } catch (error) {
        if (options?.signal?.aborted) throw error;
        return {answer: 'unavailable', reason: error?.message || 'port_unavailable'};
      }
    }});
    view.dataset.larenaPortConnected = 'true';
    if (storageWorkbench) {
      var surface = records.querySelector?.('[data-larena-composite-surface]');
      if (surface) surface.hidden = false;
    }
  }

  new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      if (mutation.type === 'attributes') {
        applyNativeInputAttributes(mutation.target);
        applyReadOnlyTable(mutation.target);
        return;
      }
      mutation.addedNodes.forEach(function (node) {
        if (node instanceof Element) {
          syncNativeInputAttributes(node);
          syncReadOnlyTables(node);
        }
      });
    });
  }).observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['autocomplete', 'data-larena-read-only', 'selectable', 'settings', 'actions'] });

  async function boot() {
    var descriptors = document.querySelectorAll('script[type="application/json"][data-larena-smart-hydration]');
    for (var index = 0; index < descriptors.length; index += 1) {
      var node = descriptors[index];
      if (node.dataset.larenaHydrated === 'true') continue;
      var descriptor = JSON.parse(node.textContent || '{}');
      await customElements.whenDefined(descriptor.component);
      hydrate(descriptor);
      node.dataset.larenaHydrated = 'true';
    }
    if (document.querySelector('sf-input')) {
      await customElements.whenDefined('sf-input');
      syncNativeInputAttributes(document);
    }
    if (document.querySelector('[data-larena-read-only="true"] sf-table')) {
      await customElements.whenDefined('sf-table');
      syncReadOnlyTables(document);
    }
    if (document.querySelector('sf-data-view')) {
      await customElements.whenDefined('sf-data-view');
      document.querySelectorAll('sf-data-view').forEach(connectCmsComposite);
    }
    window.dispatchEvent(new CustomEvent('larena-smart-ready'));
    document.documentElement.dataset.larenaSmartReady = 'true';
  }

  // A record panel loaded into the page later hydrates its own Smart elements the same way.
  window.LarenaSmartBridge = Object.freeze({
    hydrate: boot,
    // The record panel saved a record: write its row again in every list connected to the port.
    // Without a record id (a create, delete or restore) the lists ask for their page again.
    refreshRecord: function (id) {
      // Only lists answered by the host port can ask again; a list with rows in place cannot.
      var views = Array.prototype.slice.call(document.querySelectorAll('sf-data-view[data-larena-port-connected="true"]'))
        .filter(function (view) { return typeof view.larenaRefreshRecord === 'function'; });
      views.forEach(function (view) {
        if (id) void view.larenaRefreshRecord(id);
        else view.refresh?.('record-saved');
      });
      return views.length > 0;
    },
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
