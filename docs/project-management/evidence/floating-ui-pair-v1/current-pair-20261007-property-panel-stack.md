# Current Framework pair — 2026-10-07, with the property view panel stack, slots and file upload

Larena moves to the pair the Framework owner handed over on 2026-10-07: ui `27bae24b` binds the registry over build `571f3dc8`, handoff `Framework message 2026-10-07 (panel stack, port 1.4.0)`. The archive SHA-256 of both runtime trees in this package's lock equals the release lock `ui/contracts/releases/ui-571f3dc8fc5b-smart-8a820884d00e.lock.json` (Core `b6824cac…`, Smart `623feb29…`).

This pair replaces `ui-836001d88fa5-smart-fef30d766b5f`: Core `571f3dc8fc5bc93d4a5d48cfadefcaefb4a5176b` (6143 files), Smart `8a820884d00e3c7f3b310b5f5bd7fecaa267cb45` (1159 files), registry `27bae24b88640d8c6be959e7dc1296388f4be841` (file SHA-256 `daf5f3e6…`).

Framework changes carried: sf-property-view panels stack (the lower one dimmed and inert, Esc closes the top); record.open asks the host to open a related record; host content goes into the actions and footer slots and keeps its nodes; field-upload / field.upload stores a file; ports 1.0.0-1.3.0 stay accepted.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
