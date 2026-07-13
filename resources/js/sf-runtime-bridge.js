(function () {
  'use strict';

  function hydrate(descriptor) {
    var target = document.getElementById(descriptor.target);
    if (!target || descriptor.component !== target.localName) {
      throw new Error('larena-smart-hydration-target-mismatch');
    }
    if (descriptor.component === 'sf-table') {
      if (typeof target.setTableData !== 'function') {
        throw new Error('larena-smart-table-api-unavailable');
      }
      target.setTableData(descriptor.props.data || {}, 'larena-backend-hydration');
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
