# Current Framework pair — 2026-10-03, with stable table cells

Larena moves to the pair the Framework owner handed over on 2026-10-03: ui `7c07637a` binds the registry over build `a928c76c`, handoff `ui-control/source/handoff/2026-10-04-dataview-table-round-3-to-larena.md (addendum, Framework message 2026-10-04)`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-a928c76c79da-smart-8e60c9cdbf32.lock.json` (Core `241ba0d9…`, Smart `6f97a029…`).

This pair replaces `ui-26434c2bab11-smart-f0b1097df368`: Core `a928c76c79da09206fb3bf444fdad7ab4d447bb0` (6143 files), Smart `8e60c9cdbf325ae390e63384700cb06674b2aa51` (987 files), registry `7c07637ad2db61cff3c967a911b7dca97bae682a` (file SHA-256 `147ba77c…`).

Framework changes carried: Framework elements in table cells keep their place across renders (a query re-renders 0 nodes instead of 280, one changed record writes 20 attributes instead of 140 new nodes; marks and focus survive a render); the button variant cleanup (ghost reads as link, tonal primary) and neutral loading stripes.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
