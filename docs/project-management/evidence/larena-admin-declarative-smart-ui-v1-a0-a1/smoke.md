# Smoke

`admin.collection` resolves recursively to `dataview.table`, toolbar,
`sf-table` and pagination through the public facade. Child assets are emitted
once and unknown or cyclic composition fails closed.
