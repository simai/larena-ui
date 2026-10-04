# Current Framework pair — 2026-10-03, with the data view table round 2

Larena moves to the pair the Framework owner handed over on 2026-10-03: ui `6ac1b888` binds the registry over build `84c63334`, handoff `ui-control/source/handoff/2026-10-04-dataview-table-round-2-to-larena.md`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-84c633342c57-smart-1f3da4c3470e.lock.json` (Core `8424b5a6…`, Smart `fbb36890…`).

This pair replaces `ui-c051aa62458e-smart-85d1858d7632`: Core `84c633342c5771c57308b22ae68bcffab3cf7dfc` (6129 files), Smart `1f3da4c3470e8356cde785eb1045f8f1b595774a` (931 files), registry `6ac1b8881406d465ab5e6ee5d315a71f3961df90` (file SHA-256 `96f2be14…`).

Framework changes carried: six-dot resize handle with the guide only on the handle; no current-row bar (focus ring under :focus-visible only, drawn as one ring over pinned cells); personal pinning per column with a reset that clears the person's layer; the search row above crowded filters; no gap before Show more; settings changes, row clicks and Show more without re-querying, a centred loader for real re-queries; the bulk strip inside the card, sticky together with the pagination row; a five-page window with first/prev/next/last icons and a page field; data view port 1.4.0 makes pinned a personal field.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change, and the sf-pagination allow-list gains `first-label`, `page-field-label`.
