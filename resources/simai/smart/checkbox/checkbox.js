class SfCheckbox extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('role', this.getAttribute('role') || 'checkbox');
    this.setAttribute('aria-checked', this.getAttribute('aria-checked') || 'false');
  }
}

if (!customElements.get('sf-checkbox')) {
  customElements.define('sf-checkbox', SfCheckbox);
}
