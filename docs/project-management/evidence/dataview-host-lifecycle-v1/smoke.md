# Browser smoke

Actual bridge passed native Chrome lifecycle probe. Complete Root/Smart hydration, responsive and permission acceptance remain pending. No deployment performed.

To reproduce the focused probe, create a dedicated temporary HTTP directory, copy browser-probe.html to index.html, browser-probe.js to probe.js, and the candidate resources/js/admin-smart-event-bridge.js to bridge.js. Serve on loopback, open in Chrome and click Run lifecycle checks. The page clearly labels controlled ports, so this must not be represented as complete Smart acceptance.

## Real Smart integration follow-up

See `root-integration-observation.json`: matching Admin checksum restores resources, real table and server search work. Clicking row view did not open a record. This is a failed operation acceptance check, not proof of CRUD readiness. The static controlled-port probe remains separately scoped. No live site was updated.
