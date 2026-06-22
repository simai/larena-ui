class SfAdminMenu extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('role', this.getAttribute('role') || 'navigation');
  }
}

if (!customElements.get('sf-admin-menu')) {
  customElements.define('sf-admin-menu', SfAdminMenu);
}
