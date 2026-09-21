import registeredListManifest from './registered-list.type-manifest.json' with { type: 'json' };

/** Trusted product type projection. No renderer code comes from Recipe or database JSON. */
export function createRegistry(framework) {
  const builtin = [...framework.createRegistry().types.values()];
  const manifest = {
    schema: 'simai.composition.type-manifest.v1', type: 'larena.logical-file-image', version: '1.0.0',
    category: 'content', mode: 'leaf', profiles: ['ui-layout'],
    data_schema: { type: 'object', additionalProperties: false, properties: {} },
    props_schema: { type: 'object', additionalProperties: false, required: ['public_id', 'extension', 'alt'], properties: {
      public_id: { type: 'string', pattern: '^[a-zA-Z0-9-]{1,100}$' },
      extension: { type: 'string', enum: ['png', 'jpg', 'jpeg', 'webp', 'gif'] },
      alt: { type: 'string', maxLength: 500 },
    } },
    presentation: { views: ['default'], presets: [], modifiers: [] }, slots: {},
    renderer: { kind: 'builtin', name: 'larena.logical-file-image' }, assets: [], capabilities: ['html'],
  };
  const escape = value => String(value).replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]);
  return framework.createRegistry([...builtin, manifest, registeredListManifest], {
    'layout.section': ({ node, slots }) => `<section data-sf-composition-id="${escape(node.id)}" data-composition-preset="${escape(node.presentation?.preset || 'surface')}">${slots.default || ''}</section>`,
    // Static publication contains the host only; authorized rows are rendered per HTTP request in PHP.
    // Inside a layout.scope the host wraps exactly one sf-table, so it carries the endpoint name.
    'larena.registered-list': ({ node, endpoint }) => `<div data-larena-request-list="${escape(node.id)}" data-source-key="${escape(node.props.source_key)}" data-column-preset="${escape(node.props.column_preset)}"${endpoint ? ` data-sf-endpoint="${escape(endpoint)}"` : ''}></div>`,
    'larena.logical-file-image': ({ node }) => {
      const { public_id: id, extension, alt } = node.props;
      if (!/^[a-zA-Z0-9-]{1,100}$/.test(id) || !['png', 'jpg', 'jpeg', 'webp', 'gif'].includes(extension)) throw new Error('larena_logical_image_metadata_invalid');
      return `<figure data-composition-node="${escape(node.id)}"><img src="/media/${encodeURIComponent(id)}/image.${extension}" alt="${escape(alt)}" width="320" height="180" loading="lazy"></figure>`;
    },
  });
}

/** The registered list is rendered by the published sf-table element, which owns its ports. */
export function createCompositionPorts(framework) {
  return { manifests: framework.BUILTIN_PORT_MANIFESTS, bindings: { 'larena.registered-list': 'sf-table' } };
}
