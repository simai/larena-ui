# Current Framework pair — 2026-10-03, with the round-2 fixes

Larena moves to the pair the Framework owner handed over on 2026-10-03: ui `03dc7a46` binds the registry over build `ea764e22`, handoff `ui-control/source/handoff/2026-10-04-dataview-table-round-2-fixes-to-larena.md`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-ea764e2281ac-smart-66cc68814180.lock.json` (Core `316c531c…`, Smart `95b47362…`).

This pair replaces `ui-84c633342c57-smart-1f3da4c3470e`: Core `ea764e2281ac6aacb145cbc7fc53501ceee902ea` (6129 files), Smart `66cc688141805ee6bee1a2c35a87aad2bff245ea` (931 files), registry `03dc7a465c85d05cfa13302c854e145af53a9dc1` (file SHA-256 `1fe47f1e…`).

Framework changes carried: the data view draws the bulk strip at card level and only that strip sticks, with its own surface, top line and shadow; the column settings list follows the table order (pinned, visible, hidden); only the keyboard-focused row is positioned; the legacy Actions tab is gone from the column settings.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
