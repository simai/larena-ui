# Smoke

Package smoke on PHP 8.4 confirms that the default registry produces one
reference-ready `ui.button` entry in English and Russian, and that two AI JSON
serializations are byte-identical. `AdminDataviewRenderer` produces a real
`<sf-table>` artifact through `ui.dataview` and preserves escaped hydration
data.

Protected Admin route and browser acceptance remain in the integration batch;
this package smoke does not substitute for them.
