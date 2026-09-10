(function () {
  'use strict';

  function connectTablePreferences(target, form) {
    if (!form?.dataset?.larenaTablePreferences) return;
    var state = JSON.parse(form.dataset.larenaTablePreferences);
    if (!state || !Number.isInteger(state.revision)) return;
    target.setTableSettings({columnSettings: state.columns || {}}, 'larena-preferences-load');
    var revision = state.revision;
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
    window.addEventListener('beforeunload', function (event) {
      if (inFlight) { event.preventDefault(); event.returnValue = ''; }
    });
    target.addEventListener('sf-table:column-settings-change', function (event) {
      if (event.target !== target || failed) return;
      var columns = JSON.parse(JSON.stringify(event.detail?.columnSettings || {}));
      inFlight += 1;
      notice(ru ? 'Сохраняем настройки столбцов…' : 'Saving column settings…');
      // Serialize changes so a resize followed by hide cannot overwrite a newer revision.
      pending = pending.then(async function () {
        if (failed) return;
        var response = await fetch(form.dataset.larenaPreferencesUrl, {
          method: 'POST', credentials: 'same-origin',
          headers: {'Content-Type': 'application/json', Accept: 'application/json',
            'X-CSRF-TOKEN': form.dataset.larenaPreferencesCsrf},
          body: JSON.stringify({action: 'dataview.preferences.save',
            scope_ref: form.querySelector('[name="scope_ref"]').value,
            payload: {structure_id: form.querySelector('[name="structure_id"]').value,
              base_revision: revision, columns: columns}})
        });
        var receipt = await response.json();
        if (!response.ok || receipt.status !== 'ok') throw new Error('preferences-save-rejected');
        revision += 1;
      }).catch(function () {
        failed = true;
      }).finally(function () {
        inFlight -= 1;
        if (failed) notice(ru
          ? 'Настройки не сохранены. Перезагрузите страницу и повторите изменение.'
          : 'Settings were not saved. Reload the page and try the change again.');
        else if (!inFlight) notice(ru ? 'Настройки столбцов сохранены.' : 'Column settings saved.');
      });
    });
  }

  function connectPagination(pagination, form, target, appendRows) {
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
    });
    pagination.addEventListener('sf-page-size-change', function (event) {
      if (event.target !== pagination) return;
      navigate(1, event.detail?.pageSize);
    });
    var fallback = pagination.querySelector('[data-larena-pagination-next]');
    var nextHref = fallback?.getAttribute('href') || null;
    if (fallback) fallback.remove();
    var loading = false;
    var status = form.querySelector('[data-larena-pagination-status]');
    var ru = document.documentElement.lang.startsWith('ru');
    pagination.addEventListener('sf-show-more', async function (event) {
      if (event.target !== pagination || loading || !nextHref) return;
      loading = true;
      if (status) { status.hidden = false; status.textContent = ru ? 'Загружаем записи…' : 'Loading records…'; }
      try {
        var url = new URL(nextHref, window.location.href);
        if (url.origin !== window.location.origin) throw new Error('pagination-origin');
        var response = await fetch(url.href, {credentials: 'same-origin', headers: {Accept: 'text/html'}});
        if (!response.ok) throw new Error('pagination-response');
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
        appendRows(descriptor.props.data.rows);
        nextHref = nextForm.querySelector('[data-larena-pagination-next]')?.getAttribute('href') || null;
        pagination.setAttribute('current', String(nextQuery.page));
        pagination.setAttribute('total', String(descriptor.props.data.pagination.total));
        if (status) status.textContent = ru ? 'Записи загружены.' : 'Records loaded.';
      } catch (error) {
        if (status) status.textContent = ru ? 'Не удалось загрузить записи. Повторите попытку.' : 'Could not load records. Try again.';
      } finally { loading = false; }
    });
  }

  function normalizeTableRows(target, rows) {
    return (rows || []).map(function (row) {
        var actions = (row.actions || []).map(function (action) {
          var intent = action.component?.props?.value;
          if (intent !== 'larena.record.edit' && intent !== 'larena.record.view') return action;
          return Object.assign({}, action, {component: Object.assign({}, action.component, {
            props: Object.assign({}, action.component.props, {'@click': function (event) {
              event.preventDefault();
              event.stopPropagation();
              var url = new URL(window.location.href);
              url.searchParams.set('record_id', String(row.id));
              url.hash = intent === 'larena.record.view' ? 'minimal-cms-record-view' : 'minimal-cms-record-editor';
              window.location.assign(url.href);
            }})
          })});
        });
        var normalized = Object.assign({}, row, {actions: actions});
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

  function hydrate(descriptor) {
    var target = document.getElementById(descriptor.target);
    if (!target || descriptor.component !== target.localName) {
      throw new Error('larena-smart-hydration-target-mismatch');
    }
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
      var queryForm = target.closest('[data-larena-dataview-query]');
      connectTablePreferences(target, queryForm);
      var pagination = queryForm && queryForm.querySelector('sf-pagination');
      if (pagination) {
        connectPagination(pagination, queryForm, target, function (incoming) {
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
        target.addEventListener('sf-table-selection-change', function (event) {
          if (event.target !== target) return;
          var detail = event.detail || {};
          if (!Array.isArray(detail.selectedIds) || !Number.isInteger(detail.selectedCount)
            || detail.selectedCount < 0 || detail.selectedCount > rows.length
            || detail.selectedIds.length !== detail.selectedCount
            || detail.rowCount !== rows.length) return;
          pagination.setAttribute('selected-count', String(detail.selectedCount));
          pagination.setAttribute('selection-total', String(rows.length));
        });
      }
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
    window.dispatchEvent(new CustomEvent('larena-smart-ready'));
    document.documentElement.dataset.larenaSmartReady = 'true';
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
