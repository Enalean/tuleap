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
import fr_FR from "../po/fr_FR.po";
import pt_BR from "../po/pt_BR.po";
import { initGettextSync } from "@tuleap/gettext";

const gettext_provider = initGettextSync(
    "tuleap-my-lib", // domain for your translations
    { fr_FR, pt_BR },
    locale // fr_FR, pt_BR, or en_US
);

// Use the gexttext_provider !
gettext_provider.gettext("A translated sentence in english");
```
