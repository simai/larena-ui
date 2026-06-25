class SfTable extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('role', this.getAttribute('role') || 'table');
  }
}

if (!customElements.get('sf-table')) {
  customElements.define('sf-table', SfTable);
}
