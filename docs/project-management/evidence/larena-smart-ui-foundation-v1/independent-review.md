# Independent Review

Batch B0 review found no blocker to implementation. It identified one mandatory
scope rule: component and AI catalogs must share keys/order and absolute local
manifest paths must not leak into generated projections.

An early B1 review found that PHP array union could allow a manifest-supplied
`label` or `option_labels` value to override localized catalog text. The
projection now uses an authoritative localized replacement, and a regression
test injects both conflicting values.

The UI package review is green. Protected Admin consumption and current browser
runtime acceptance remain cross-package integration gates.

The first B2 manifest draft exposed only sizes `1`, `2` and `3` for textarea.
Independent comparison with the pinned `simai/ui` CSS showed that `1/3` and
`1/2` are also implemented, so the manifest and RU/EN option labels were
expanded to the complete five-value source-backed set. Input was aligned to the
same pinned size evidence.

The initial projection grammar also rejected the real `sf-dropdown:change` and
`modal:*` source events. The grammar was widened only to stable colon-separated
tokens; explicit regression cases continue to reject unsafe or malformed event
names. The pinned pagination lock advertises `main` while the paired source
reads `middle`; neither disputed prop is exposed by the manifest, so the
library does not fabricate a supported control while the upstream lock gap is
tracked.

The first alert and pagination manifests also classified their emitted `sf-*`
`CustomEvent` values as native DOM events. Review aligned them to `custom`;
only native `input`, `change`, focus, keyboard and pointer events retain the
`dom` kind.
