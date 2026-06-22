class SfProgressScale extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('role', this.getAttribute('role') || 'progressbar');
    this.setAttribute('aria-valuemin', this.getAttribute('aria-valuemin') || '0');
    this.setAttribute('aria-valuemax', this.getAttribute('aria-valuemax') || '100');
  }
}

if (!customElements.get('sf-progress-scale')) {
  customElements.define('sf-progress-scale', SfProgressScale);
}
