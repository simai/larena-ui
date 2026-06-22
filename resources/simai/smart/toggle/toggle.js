class SfToggle extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('role', this.getAttribute('role') || 'switch');
    this.setAttribute('aria-checked', this.getAttribute('aria-checked') || 'false');
  }
}

if (!customElements.get('sf-toggle')) {
  customElements.define('sf-toggle', SfToggle);
}
