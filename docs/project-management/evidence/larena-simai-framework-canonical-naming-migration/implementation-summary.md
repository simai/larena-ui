# Implementation summary

The `larena/ui` runtime contract now exposes `SfElementBackendRenderer` through
renderer ID `ui.sf_element`. The pinned lock moved to
`resources/sf/runtime-lock.json`, and its immutable pair ID now starts with
`sf-v`. Source-backed resource directories, asset keys, PHP constants and
descriptor methods use the corresponding `sf`, `SF` and `Sf` forms.

The action-link runtime marker is `larena/ui:sf_action_link`. Smart manifests
refer to `ui.sf_element` and the new lock path. Atlas copy presents `Button` /
`Кнопка`, while descriptions and framework-specific labels use the full
product name **Simai Framework**.

README, package metadata, launch context, validation rules and focused tests
were synchronized. The launch context contains explicit deletion tombstones
for tracked legacy paths so the scope checker accounts for the rename rather
than silently ignoring removals. Historical evidence files were not edited.

The package-owned catalog CSS for buttons, tags and pagination, plus the
standalone source-backed button carrier, was refreshed byte-for-byte from the
pinned `ui/distr/component/*/css` source. Both button copies share the same
source and checksum. The resulting
SHA-256 values are `4b3d7d38214c28417d0b097cdfbb1f05a6f1b5d6ac12d3735ffd0de0a25886ce`,
`d255c73fe824561c9dc39fa1c067d5724ea3d10fecf21f5f3f438cf5996cb63e`
and `21136fa97dbef25a43ac93d6f52df7c4871d55dd043e108206ab23a00f534389`.
The pinned source repository was read only and was not modified.

No upstream commit, source hash, `<sf-*>` tag, `SF` global, version tag or
`simai.framework.*` asset key changed.
