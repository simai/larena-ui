class SfTag extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('role', this.getAttribute('role') || 'status');
  }
}

if (!customElements.get('sf-tag')) {
  customElements.define('sf-tag', SfTag);
}
