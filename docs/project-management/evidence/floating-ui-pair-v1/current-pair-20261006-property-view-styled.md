# Current Framework pair — 2026-10-06, with sf-property-view styled and tag-loaded

Larena moves to the pair the Framework owner handed over on 2026-10-06: ui `acc23668` binds the registry over build `2bb86850`, handoff `Framework message 2026-10-06 (sf-property-view fixes)`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-2bb868506729-smart-b4638dec898e.lock.json` (Core `67285ada…`, Smart `0c09b654…`).

This pair replaces `ui-3ce7598e932f-smart-f999163c2388`: Core `2bb8685067293a9c3b85bfdafef949b6a9b7265f` (6143 files), Smart `b4638dec898e72790cf2c1e21d341535b0830b40` (1027 files), registry `acc236682142f4987a97cdf1a2c3368e8abf3fd0` (file SHA-256 `e447e808…`).

Framework changes carried: sf-property-view renders in its own light DOM with its stylesheet (group cards, one or two columns, the sticky action bar); rule.js for sf-property-view and sf-data-view loads them and their relations by tag (icons, progress scale); {value, text} shows the host's formatted text; record-id and collection reach the port.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
