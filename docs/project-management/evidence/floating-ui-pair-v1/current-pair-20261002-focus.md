# Current Framework pair — 2026-10-02, focus fix

Larena moves to the pair the Framework owner bound on 2026-10-02 (ui `ecb14593`, "contracts: bind the generated pair ui-227b90a6355a-smart-5c48c415d04d"; release lock `ui/contracts/releases/ui-227b90a6355a-smart-5c48c415d04d.lock.json`, status `bounded`). The archive SHA-256 of both runtime trees in this package's lock equals the release lock's.

This pair replaces `ui-f36cee75e553-smart-ae58fee26055`: Core `227b90a6355af29b168bde953810265ba82e5ef2` (archive SHA-256 `2b21cf8d…`, 6089 files), Smart `5c48c415d04d1443d6d6ad6c574caaa3020a3ae6` (archive SHA-256 `6b4d6740…`, 785 files), registry `ecb1459352a26667bcb48ed2f3fc09c466e510ce` (file SHA-256 `70a87a25…`).

Framework changes carried since the previous pin: a mouse click no longer moves the focus ring onto the bare control inside a field (the field's border marks the focus after a click, the ring around the whole field stays for the keyboard); country flags ship as images in a rectangular and a round set, and the phone field draws them instead of emoji.

The runtime lock keeps Larena's component and attribute lists; only the pair, bundle, source and registry entries change.
