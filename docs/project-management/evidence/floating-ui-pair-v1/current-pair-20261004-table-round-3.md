# Current Framework pair — 2026-10-03, with the data view table round 3

Larena moves to the pair the Framework owner handed over on 2026-10-03: ui `1fa28f63` binds the registry over build `7e13732d`, handoff `ui-control/source/handoff/2026-10-04-dataview-table-round-3-to-larena.md`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-7e13732dc09f-smart-c80a0f2e441b.lock.json` (Core `a5f07abf…`, Smart `26d1d2a2…`).

This pair replaces `ui-66bf83821f2b-smart-66cc68814180`: Core `7e13732dc09fddf9ebf939b2b460951a0abb8283` (6143 files), Smart `c80a0f2e441b5739fe8474ea3cc9569699d5b44b` (987 files), registry `1fa28f63d9e248b8d0c94611c31ec74bbcce498a` (file SHA-256 `3ccc1927…`).

Framework changes carried: the bulk strip has the surface radius; the column width is taken by an invisible strip on the header line (no handle) and the guide follows the edge; even space around Show more (a stray demo modal removed); no status line above the table, refusals as an sf-alert notice with a sentence; toolbar actions menu (setToolbarActions, more_vert); view.save carries settings.filter_fields; the page field is as wide as its number; record.open and single-record writes no longer re-render the rows; the admin menu search panel (search-mode=panel, opt-in) from ui-e27c1c45c315-smart-b8322de1ead6.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
