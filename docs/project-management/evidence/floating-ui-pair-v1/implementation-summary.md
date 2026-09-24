# Implementation summary

- Pinned ui-1f1c9d42d964-smart-121e8882d016 (ui 1f1c9d42, ui-smart 121e8882, contract registry from ui 405d9e96) and its immutable runtime artifact.
- Runtime archives match the ui-control handoff (ui distr 39d257af…, ui-smart smart 9df5a362…) before packaging.
- ui-eb212efe40cd-smart-9e8d8e762e03 stays available for rollback.
- The CMS list shows the leading record name as a Framework link button that opens the record panel; the runtime bridge wires record intents on cells as on row actions. The Dataview stylesheet revision follows its link and narrow-column rules.
- Then pinned ui-1f1c9d42d964-smart-6c5d313aca4d (ui 1f1c9d42 unchanged, ui-smart 6c5d313a, contract registry from ui 7c8a7659): sf-table create, template-select and sort-change intents, badge filter options, system actions column, table menus on SF.Position and bubbling sf-drawer events; archives match the ui-control handoff (smart 06f73701…).
- ui.pagination accepts action-for-all-label, the label of the Framework "for all" checkbox used by the CMS bulk delete of every record matching the filter.
- Then pinned ui-d81ccde2bdd5-smart-d448fb5563cc: keyboard-reachable filter chips, bubbling modal events, menu width, and sf-pagination 1.0.1 where the "for all" checkbox follows its real state; archives match the ui-control handoff (core 04635d8c…, smart 0d61e1ab…).
- The runtime bridge now gives every hydrated instance one lifecycle: listeners, observers and requests belong to it, removal aborts them, and a response that is no longer the newest is dropped (simai.dynamic-composite-component: sequenced-responses, dispose-releases).
- Then pinned ui-d81ccde2bdd5-smart-a916bbadf3aa (smart.data-view 1.6.0, data view stage A): sf-table-template-delete, instance-scoped identifiers and data-state/aria-busy on the table; archives match the ui-control handoff (smart 4c8dd29f…, registry 698655ea…).
- Then pinned ui-bc8dfd7e4bdb-smart-81741eac168d (data view stage B): new Core bc8dfd7e with type-manifest events, data bindings and settings persistence; archives match the ui-control handoff (core 57ddf023…, smart c03fb468…, registry 072fe96b…).
- Then pinned ui-cb1cda301648-smart-81741eac168d: Core cb1cda30 whose manifests declare their own version (smart manifest 2.1.0, composition type manifest 1.1.0); standards reissued as 1.0.2.
- Then pinned ui-56cd91e1d7a3-smart-903ad66c4f4f: Stage C declares simai.dataview-port 1.0.0 on sf-table; host conformance remains to be proven
