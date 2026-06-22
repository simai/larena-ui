class SfTreeItem extends HTMLElement {
  connectedCallback() {
    if (this.dataset.larenaCarrierReady === '1') {
      return;
    }

    this.dataset.larenaCarrierReady = '1';
    this.setAttribute('role', this.getAttribute('role') || 'treeitem');
  }
}

if (!customElements.get('sf-tree-item')) {
  customElements.define('sf-tree-item', SfTreeItem);
}
