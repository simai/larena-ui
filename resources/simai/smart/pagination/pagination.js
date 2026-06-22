class SfPagination extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('role', this.getAttribute('role') || 'navigation');
    this.setAttribute('aria-label', this.getAttribute('aria-label') || 'Pagination');
  }
}

if (!customElements.get('sf-pagination')) {
  customElements.define('sf-pagination', SfPagination);
}
