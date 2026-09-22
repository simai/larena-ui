# Implementation summary

- Pinned ui-1f1c9d42d964-smart-121e8882d016 (ui 1f1c9d42, ui-smart 121e8882, contract registry from ui 405d9e96) and its immutable runtime artifact.
- Runtime archives match the ui-control handoff (ui distr 39d257af…, ui-smart smart 9df5a362…) before packaging.
- ui-eb212efe40cd-smart-9e8d8e762e03 stays available for rollback.
- The CMS list shows the leading record name as a Framework link button that opens the record panel; the runtime bridge wires record intents on cells as on row actions. The Dataview stylesheet revision follows its link and narrow-column rules.
- Then pinned ui-1f1c9d42d964-smart-6c5d313aca4d (ui 1f1c9d42 unchanged, ui-smart 6c5d313a, contract registry from ui 7c8a7659): sf-table create, template-select and sort-change intents, badge filter options, system actions column, table menus on SF.Position and bubbling sf-drawer events; archives match the ui-control handoff (smart 06f73701…).
