# @tuleap/gettext

In order to let TypeScript understand `.po` file types without complaining about unknown definitions,
write a `pofile-shim.d.ts` declaration file like this:

```typescript
//src/pofile-shim.d.ts
declare module "*.po" {
    import { GettextParserPoFile } from "@tuleap/gettext";
    const content: GettextParserPoFile;
    export default content;
}
```

```typescript
//src/index.ts
import french_translations from "../po/fr_FR.po";
import { initGettextSync } from "@tuleap/gettext";

const gettext_provider = initGettextSync(
    "tuleap-my-lib", // domain for your translations
    french_translations,
    locale // fr_FR or en_US
);

// Use the gexttext_provider !
gettext_provider.gettext("A translated sentence in english");
```
