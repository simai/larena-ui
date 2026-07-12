# UI package implementation summary

Added the canonical `ui.button` manifest, valid SF5 variant presets and
cross-field constraints. `SmartComponentReference` normalizes allowlisted
controls, mirrors text into the accessible name and links loading to disabled.
`SmartInvocationExampleBuilder` produces inert PHP and `<sf-button>` examples
from the same normalized props. The real preview still goes through
`SmartManager` and the existing SF5 renderer.

`SmartPropsValidator` is shared by `SmartManager` and the example builder, so
direct calls and generated examples reject unknown props, blank accessible
names, invalid enums and cross-field constraints consistently. `ui.button`
also declares and verifies the exact pinned-runtime asset requirements.
