(() => {
  let returnFocus = null;
  document.addEventListener('click', (event) => {
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
