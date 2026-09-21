# Composition regions and routing in Larena UI

The pinned Framework pair `ui-2b9aa9635ad0-smart-db547bb87b6b` publishes named
regions (`layout.regions`, `layout.region`), scope routing (`layout.scope`) and
editor field kinds for regions. This package connects them to Larena without
extending the Framework grammar.

## Registered lists as port elements

`resources/composition/larena-registry.mjs` exports `createCompositionPorts`,
which binds the product type `larena.registered-list` to the published
`sf-table` element: `{manifests: BUILTIN_PORT_MANIFESTS, bindings:
{"larena.registered-list": "sf-table"}}`. The Layout recipe adapter passes this
registry to `resolveRecipe` (`compositionPorts`) and to `render`
(`options.ports`). The table owns its ports: output `selection` and input
`context`, both `record-ids.v1`.

The compiled snapshot holds only the list host:
`<div data-larena-request-list=… data-source-key=… data-column-preset=… data-title=…
data-sf-endpoint=…>`. The endpoint attribute sits on this wrapper, which
contains exactly one `sf-table` after the Root fills it per request.

## Host side of a route

`resources/js/admin-smart-event-bridge.js` reads the nearest
`sf-composition-scope` routes. A list that is the `to` endpoint of a route:

- starts empty and does not query the server until a selection arrives;
- on `sf-table-query-intent` with `reason: "context"` sends one query with the
  server filter `record_id in [...]` and answers `applyQueryResult(sequence, rows)`;
- keeps the delivered selection as a filter for later search, sort and paging;
- answers an empty selection locally with no request, and refuses more than 100
  identifiers (the Dataview filter limit) without a request;
- lets the table reject a stale answer: an older, slower response never replaces
  newer rows.

A list that is only a route source ignores context intents. Tests:
`tests/JavaScript/composition-routing-host.test.mjs`.

## Rollback

Repin `resources/sf/runtime-lock.json` to `ui-4c6c75b1847a-smart-dd973536c66f`
and restore the previous registry module. Documents with `layout.regions`,
`layout.region` or `layout.scope` are then refused as `type_unknown`; earlier
documents keep the same digest and HTML.
