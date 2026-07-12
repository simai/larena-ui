(() => {
  let returnFocus = null;
  let referenceSubmitTimer = null;

  const releaseInlinePreviewAutofocus = () => {
    window.requestAnimationFrame(() => {
      const active = document.activeElement;
      if (!(active instanceof HTMLElement) || !active.matches('[data-sf-modal-panel]')) return;
      const modal = active.closest('sf-modal[display="inline"]');
      if (!modal?.closest('.larena-lab-preview, .larena-smart-reference, .larena-recipe-artifact')) return;
      active.blur();
    });
  };

  window.addEventListener('larena-smart-ready', releaseInlinePreviewAutofocus, { once: true });
  if (document.documentElement.dataset.larenaSmartReady === 'true') releaseInlinePreviewAutofocus();

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
