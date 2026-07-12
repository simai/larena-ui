# UI package smoke

- The facade resolves the application `SmartManager` and returns its real
  `FrontendRenderArtifact` for `ui.button`.
- Missing and invalid manager resolvers fail closed with stable error codes.
- Valid empty optional radius input remains omitted from rendered props after
  Laravel middleware normalizes the query value to `null`.
- Generated PHP uses `Larena\Ui\Facades\Smart`; generated HTML remains the
  pinned-runtime `<sf-button>` artifact.
- No SF5 source, runtime revision or lock file changed.
