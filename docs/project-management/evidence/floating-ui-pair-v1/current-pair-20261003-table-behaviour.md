# Current Framework pair — 2026-10-03, data view table behaviour

Larena moves to the pair the Framework owner handed over on 2026-10-03 for group 3 of the data view table request: ui `c434bbc2` binds the registry over build `ed549e72`, handoff `ui-control/source/handoff/2026-10-03-dataview-table-behaviour-to-larena.md`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-ed549e7282ef-smart-6a58ac134bdb.lock.json` (Core `60309eb7…`, Smart `3c6a2e61…`).

This pair replaces `ui-5e466412cac0-smart-c969ab09e15b`: Core `ed549e7282efbc0c4af0a2750abc38e2615e7696` (6129 files), Smart `6a58ac134bdb7dabe1ec77dd671f925ddb065721` (829 files), registry `c434bbc20a5c6c03057501e836c404643d5be60f` (file SHA-256 `65f2a16a…`).

Framework changes carried: a click makes a row current and a double click opens it when the row offers view; Shift marks a range; the box, the row menu and the `role: "title"` column stay pinned with an edge shadow; the five-place page switcher; Show more leaves when nothing follows; the bulk panel fixed at the bottom while rows are marked, with `action-choose-label`, `actions-region-label`, `clear-selection-label` and `sf-selection-clear`; the data view port 1.2.0 declares the column role. The sf-pagination allow-list gains the three new label attributes; the host port bridge declares port 1.2.0.
