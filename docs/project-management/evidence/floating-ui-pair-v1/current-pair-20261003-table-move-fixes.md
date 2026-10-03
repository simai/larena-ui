# Current Framework pair — 2026-10-03, with the column move and pinned-cell fixes

Larena moves to the pair the Framework owner handed over on 2026-10-03: ui `18f68dca` binds the registry over build `c051aa62`, handoff `ui-control/source/handoff (Framework message 2026-10-03, pair ui-c051aa62458e-smart-85d1858d7632)`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-c051aa62458e-smart-85d1858d7632.lock.json` (Core `b735cd3b…`, Smart `5b646c8d…`).

This pair replaces `ui-c33663c3822b-smart-5e974280513e`: Core `c051aa62458e74f78298925c80e92e1031ac0058` (6129 files), Smart `85d1858d76320da03a9e8afcc4ca99f102444aa0` (879 files), registry `18f68dcaa7d0fe1526d407b6a54952b5354f3532` (file SHA-256 `d5a3b15e…`).

Framework changes carried: the column drop is decided at release; a move renumbers only visible columns and keeps the order of hidden ones; the current row is keyed like any row (id, value, position); row states are painted as a surface plus a tint so a pinned cell no longer darkens the hover.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
