class SfAvatar extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('role', this.getAttribute('role') || 'img');
    this.setAttribute('aria-label', this.getAttribute('aria-label') || 'Avatar');
  }
}

if (!customElements.get('sf-avatar')) {
  customElements.define('sf-avatar', SfAvatar);
}
