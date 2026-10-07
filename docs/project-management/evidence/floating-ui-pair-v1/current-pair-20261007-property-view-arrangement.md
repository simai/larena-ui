# Current Framework pair — 2026-10-07, with the property view arrangement, related records and phone step

Larena moves to the pair the Framework owner handed over on 2026-10-07: ui `6e386b94` binds the registry over build `b67a4808`, handoff `Framework messages 2026-10-07 (panel width, ports 1.5.0-1.7.0, phone step)`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-b67a48087c27-smart-2ce766aa8dcc.lock.json` (Core `deb188de…`, Smart `0757b1af…`).

This pair replaces `ui-27bae24b8864-smart-7013a3918410`: Core `b67a48087c27a311b383279a8ced23225879c819` (6143 files), Smart `2ce766aa8dcc2eb05ad0eb12018c70ec81f60ecc` (1359 files), registry `6e386b948687d7394d874784768cc4dce0c7b438` (file SHA-256 `71efd13e…`).

Framework changes carried: panel-width takes an exact length and wins over panel-size; the drawer heads with its own title; a datetime keeps its clock when its day is edited; port 1.5.0 declares the field keys the renderers read; port 1.6.0 configures the arrangement one layer at a time (view.save, view.reset, the view block, hiddenBy) behind view-settings; port 1.7.0 adds related groups whose embedded, styled sf-data-view asks the page for its own port (sf-host-port-request); below the drawer breakpoint the panel is full screen with sections as a list; ports 1.0.0-1.6.0 stay accepted.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
