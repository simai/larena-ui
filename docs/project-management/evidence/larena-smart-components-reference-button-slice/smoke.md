# UI Package Smoke

- The manifest resolves `ui.button` to the pinned SF5 `sf-button` runtime.
- Default, configured preset and loading scenarios pass shared normalization.
- Blank/whitespace text, unknown props, object values and a missing runtime
  pair fail closed.
- The real render artifact and generated frontend/PHP examples expose the same
  normalized props.
- The explicit five-key asset requirement set matches the verified runtime
  asset graph; the SF5 lock file hash remains unchanged.

Browser-level catalog, theme and responsive evidence is recorded in the root
and `larena/admin` evidence packets.
