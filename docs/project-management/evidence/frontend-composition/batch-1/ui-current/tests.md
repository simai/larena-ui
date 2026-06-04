# Tests

Commands executed:

```bash
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer validate --strict
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer dump-autoload
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer run validate:larena
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer run lint
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer run analyse
PATH=/opt/homebrew/opt/php@8.3/bin:$PATH /Applications/ServBay/package/bin/composer run test
```

Result: passed.

Covered assertions:

- smart manifests require stable component key and props schema;
- backend-first native render can be safe without frontend runtime;
- hydratable components require explicit hydration metadata and props hash;
- UI asset graph separates critical requirements and leaves final paths to core.assets;
- design packs reject content records, platform-specific IDs and runtime cache;
- atlas/playground artifacts are generated from canonical manifests;
- UI resource pack activation is owned by core.assets.
