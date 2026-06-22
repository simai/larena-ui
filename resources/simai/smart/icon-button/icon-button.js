class SfIconButton extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('role', this.getAttribute('role') || 'button');
    this.setAttribute('tabindex', this.getAttribute('tabindex') || '0');
  }
}

if (!customElements.get('sf-icon-button')) {
  customElements.define('sf-icon-button', SfIconButton);
}
