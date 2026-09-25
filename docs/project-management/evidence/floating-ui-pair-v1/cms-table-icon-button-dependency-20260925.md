# CMS table icon-button dependency — 2026-09-25

## Observed failure

In the authenticated Larena CMS page, the browser reported `SfTable.hoverEvents/outEvent: button.setHidden is not a function` while hovering the table header. The pinned Framework `smart/table/js/table.js` renders `sf-icon-button` column-resize controls and calls their `setHidden` method. The method exists on the Framework `SfIconButton` class, but the Larena `sf-table` runtime lock did not declare `sf-icon-button` as a dependency. The host therefore served the table without the icon-button definition, leaving plain custom elements at the call site.

## Laboratory correction

- `sf-table` now requires `sf-icon-button` in `resources/sf/runtime-lock.json`.
- The `ui.dataview` Smart manifest declares the icon and icon-button assets, matching the resolved graph.
- Admin activation loads icon, icon-button, table, pagination and data-view JavaScript in that order. Its SF boot digest matches the revised runtime lock: `34a7123346896c9431201710740ee1b3451b594cff5cba7ba03bdb2c343368ac`.
- Asset graph and Admin activation assertions cover the dependency and order.

This addresses the hover/resize control error. The earlier empty table was caused by a separate missing server port binding, which the Backend owner is handling. The browser also reported duplicate `sf-table`/`sf-pagination` definitions because the pinned `smart/data-view/js/data-view.js` includes those classes while the host loads their standalone files. `SfBaseElement.define` keeps the existing definition; this warning has no demonstrated effect on table data. The Framework owner should remove duplicate registration in a future runtime pair.

## Evidence boundary

UI `composer run quality:gate` passed with the exact pinned Core and PHP 8.4; Admin gate result and immutable package SHAs are recorded in the Larena Frontend Specs handoff after commit. The corrected assets are not yet integrated into the canonical 23-package Root. Recheck the hover control in the authenticated browser after that integration.
