class SfBreadcrumbs extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('aria-label', this.getAttribute('aria-label') || 'Breadcrumbs');
  }
}

if (!customElements.get('sf-breadcrumbs')) {
  customElements.define('sf-breadcrumbs', SfBreadcrumbs);
}
