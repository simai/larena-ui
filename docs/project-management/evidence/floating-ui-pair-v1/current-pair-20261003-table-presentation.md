# Current Framework pair — 2026-10-03, data view table presentation

Larena moves to the pair the Framework owner handed over on 2026-10-03 for group 2 of the data view table request: ui `6a5608b7` binds the registry, handoff `ui-control/source/handoff/2026-10-03-dataview-table-presentation-to-larena.md`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-5e466412cac0-smart-c969ab09e15b.lock.json` (Core `d237405f…`, Smart `8207bee2…`).

This pair replaces `ui-5e466412cac0-smart-a794c2e9c492`: Core `5e466412cac0b202a9965f0b228938964690916a` (unchanged), Smart `c969ab09e15bd6c5e3bb9e00748780b6431dc4e3`, registry `6a5608b70b7f7141aa3263e19bf8336b4a509b23` (file SHA-256 `640a1653…`).

Framework changes carried: the sorted column is answered in its header (primary label and icon, a mark under the header, a muted icon on hover); rows carry `data-selected` and paint hover, selected and selected-hover from the theme; the resize guide is one outline-variant line through the body only; the sticky head paints its own surface. Larena drops its temporary row-state and head-background rules.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
