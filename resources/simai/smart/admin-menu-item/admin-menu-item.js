class SfAdminMenuItem extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('role', this.getAttribute('role') || 'link');
  }
}

if (!customElements.get('sf-admin-menu-item')) {
  customElements.define('sf-admin-menu-item', SfAdminMenuItem);
}
