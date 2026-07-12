(() => {
  let returnFocus = null;
  let referenceSubmitTimer = null;
  let inlinePreviewFocusReleaseArmed = false;
  let inlinePreviewFocusReleaseTimer = null;

  const setupCatalogSearch = () => {
    const panel = document.querySelector('[data-larena-catalog-search-panel]');
    const input = panel?.querySelector('[data-larena-catalog-search]');
    const grid = document.querySelector('[data-larena-ui-lab="atlas"]');
    const cards = Array.from(grid?.querySelectorAll('[data-larena-catalog-card]') || []);
    const results = panel?.querySelector('[data-larena-catalog-results]');
    const clear = panel?.querySelector('[data-larena-catalog-clear]');
    const empty = document.querySelector('[data-larena-catalog-empty]');
    const reset = empty?.querySelector('[data-larena-catalog-reset]');

    if (!(panel instanceof HTMLElement) || !(input instanceof HTMLInputElement) || !(grid instanceof HTMLElement) || cards.length === 0) return;

    const normalize = (value) => String(value).trim().toLocaleLowerCase();
    const update = () => {
      const query = normalize(input.value);
      let visible = 0;

      cards.forEach((card) => {
        const matches = query === '' || normalize(card.dataset.larenaCatalogSearchText).includes(query);
        card.hidden = !matches;
        if (matches) visible += 1;
      });

      grid.hidden = visible === 0;
      if (empty instanceof HTMLElement) empty.hidden = visible !== 0;
      if (clear instanceof HTMLButtonElement) clear.hidden = query === '';
      if (results instanceof HTMLElement) {
        results.textContent = String(results.dataset.resultsTemplate || '')
          .replace(':visible', String(visible))
          .replace(':total', String(cards.length));
      }
    };
    const clearSearch = () => {
      input.value = '';
      update();
      input.focus();
    };

    panel.hidden = false;
    input.addEventListener('input', update);
    input.addEventListener('search', update);
    input.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape' || input.value === '') return;
      event.preventDefault();
      clearSearch();
    });
    clear?.addEventListener('click', clearSearch);
    reset?.addEventListener('click', clearSearch);
    update();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupCatalogSearch, { once: true });
  } else {
    setupCatalogSearch();
  }

  const isInlinePreviewModalPanel = (target) => {
    if (!(target instanceof HTMLElement) || !target.matches('[data-sf-modal-panel]')) return false;
    const modal = target.closest('sf-modal[display="inline"]');
    return Boolean(modal?.closest('.larena-lab-preview, .larena-smart-reference, .larena-recipe-artifact'));
  };

  const disarmInlinePreviewFocusRelease = () => {
    inlinePreviewFocusReleaseArmed = false;
    window.clearTimeout(inlinePreviewFocusReleaseTimer);
    document.removeEventListener('focusin', releaseInlinePreviewAutofocus, true);
    document.removeEventListener('pointerdown', disarmOnInitialUserInput, true);
    document.removeEventListener('keydown', disarmOnInitialUserInput, true);
  };

  const releaseInlinePreviewAutofocus = (event) => {
    if (!inlinePreviewFocusReleaseArmed) return;
    const target = event instanceof FocusEvent ? event.target : document.activeElement;
    if (!isInlinePreviewModalPanel(target)) return;
    target.blur();
    const previousTabindex = document.body.getAttribute('tabindex');
    document.body.setAttribute('tabindex', '-1');
    document.body.focus({ preventScroll: true });
    if (previousTabindex === null) document.body.removeAttribute('tabindex');
    else document.body.setAttribute('tabindex', previousTabindex);
    disarmInlinePreviewFocusRelease();
  };

  const disarmOnInitialUserInput = (event) => {
    if (event.isTrusted) disarmInlinePreviewFocusRelease();
  };

  const armInlinePreviewFocusRelease = () => {
    if (inlinePreviewFocusReleaseArmed) return;
    inlinePreviewFocusReleaseArmed = true;
    document.addEventListener('focusin', releaseInlinePreviewAutofocus, true);
    document.addEventListener('pointerdown', disarmOnInitialUserInput, true);
    document.addEventListener('keydown', disarmOnInitialUserInput, true);
    inlinePreviewFocusReleaseTimer = window.setTimeout(disarmInlinePreviewFocusRelease, 1500);
    releaseInlinePreviewAutofocus();
  };

  window.addEventListener('larena-smart-ready', armInlinePreviewFocusRelease, { once: true });
  if (document.documentElement.dataset.larenaSmartReady === 'true') armInlinePreviewFocusRelease();

  const submitReferenceForm = (form, delay = 0) => {
    window.clearTimeout(referenceSubmitTimer);
    referenceSubmitTimer = window.setTimeout(() => {
      if (form.dataset.larenaSmartReferenceSubmitting === '1') return;
      form.dataset.larenaSmartReferenceSubmitting = '1';
      if (typeof form.requestSubmit === 'function') form.requestSubmit();
      else form.submit();
    }, delay);
  };

  document.addEventListener('change', (event) => {
    const control = event.target.closest('[data-larena-smart-reference-form] input, [data-larena-smart-reference-form] select');
    if (!control) return;
    submitReferenceForm(control.form);
  });

  document.addEventListener('input', (event) => {
    const control = event.target.closest('[data-larena-smart-reference-form] input[type="text"]');
    if (!control) return;
    submitReferenceForm(control.form, 700);
  });

  document.addEventListener('submit', (event) => {
    if (event.target.matches('[data-larena-smart-reference-form]')) {
      event.target.dataset.larenaSmartReferenceSubmitting = '1';
    }
  });

  document.addEventListener('click', (event) => {
    const sfModalTrigger = event.target.closest('[data-larena-sf-modal-trigger]');
    if (sfModalTrigger) {
      const modal = document.getElementById(sfModalTrigger.dataset.larenaSfModalTrigger);
      returnFocus = sfModalTrigger.querySelector('sf-button') || sfModalTrigger;
      if (typeof modal?.open === 'function') modal.open();
      else modal?.setAttribute('open', '');
      return;
    }
    const open = event.target.closest('[data-larena-dialog-open]');
    if (open) {
      const dialog = document.getElementById(open.dataset.larenaDialogOpen);
      if (dialog instanceof HTMLDialogElement) {
        returnFocus = open;
        dialog.showModal();
        dialog.querySelector('[data-larena-dialog-close]')?.focus();
      }
      return;
    }
    const close = event.target.closest('[data-larena-dialog-close]');
    if (close) {
      close.closest('dialog')?.close();
      returnFocus?.focus();
    }
    const media = event.target.closest('.larena-lab-media-option');
    if (media) {
      media.parentElement?.querySelectorAll('.larena-lab-media-option').forEach((item) => item.setAttribute('aria-pressed', item === media ? 'true' : 'false'));
    }
  });
  document.addEventListener('close', (event) => {
    if (event.target instanceof HTMLDialogElement) returnFocus?.focus();
  }, true);
})();
