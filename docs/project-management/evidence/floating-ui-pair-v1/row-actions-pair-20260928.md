# Row actions pair — 2026-09-28

Framework gave `sf-table` a `row-actions` attribute (evidence `ui-control/source/workflow/evidence/2026-09-28-row-actions/evidence.md`, `34412dd0`). The table used to switch its row action column on together with the toolbar create button through one `actions` attribute; `row-actions="false"` now hides only the column, so Larena's admin data view pages, whose row menu is the only entry to a row's actions, keep the create button without reaching into the table's column-visibility state. The table manifest moves from 1.8.0 to 1.9.0 and the runtime lock lists the attribute for `sf-table`.

The pair also carries the Framework changes published since the previous Larena pin: the named axes across components, one focus ring with the alpha ramp appearances, and theme roles declared once with `light-dark()`.

This pair replaces `ui-15249a155c60-smart-7d1475a3e4c3`: Core `e5a1228a9d8ac6d74615ade3a17d8636ea9065f8` (distr tree `6e17569c…`, archive SHA-256 `5e65fb6e…`), Smart `c184f5944ae60d68f3f34d54773c051b35b3d9f8` (smart tree `dd6c5af6…`, archive SHA-256 `5613bc6f…`), registry `12d25bf7511d3ef096f6b39c01d110034945fa4f` (file SHA-256 `4ea0ab27…`). Framework verification: owner gate 998 tests, 996 passed, 0 failures, 2 skipped; two byte-identical product waves on Node 20.19.5; registry tests 30 OK.
