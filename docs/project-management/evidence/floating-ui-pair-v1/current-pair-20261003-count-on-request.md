# Current Framework pair — 2026-10-03, with the table fixes and count on request

Larena moves to the pair the Framework owner handed over on 2026-10-03: ui `c051aa62` binds the registry over build `c33663c3`, handoff `ui-control/source/handoff/2026-10-03-count-on-request-to-larena.md`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-c33663c3822b-smart-5e974280513e.lock.json` (Core `1d6b0191…`, Smart `e447f826…`).

This pair replaces `ui-ed549e7282ef-smart-6a58ac134bdb`: Core `c33663c3822b2e37458dcb457339c2a423cd2530` (6129 files), Smart `5e974280513ef09088c7dfdaee0f08112701e934` (879 files), registry `c051aa62458e74f78298925c80e92e1031ac0058` (file SHA-256 `b68ec231…`).

Framework changes carried: column move by the header label survives a re-render and no longer stalls the page; the title-role column pins where the person placed it; a click anywhere in a row makes it current without re-rendering; the data view port 1.3.0 can answer without a total (has_next, query.count behind count-on-request).

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change, and the sf-pagination allow-list gains `count-known`, `can-count`, `counting`, `has-next`, `show-count-label`, `counting-label`.
