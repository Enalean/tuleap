# @tuleap/vue3-gettext-init

## Purpose

Instantiate [`vue3-gettext`](https://github.com/jshmrtn/vue3-gettext) with the appropriate translation for the current
Tuleap user.

## Usage

```ts
import { createApp } from "vue";
import { initVueGettext, getPOFileFromLocale } from "@tuleap/vue3-gettext-init";

const app = createApp();
app.use(initVueGettext(
    (locale: string) =>
        import(
            /* webpackChunkName: "some-app-po" */ "./po/" + getPOFileFromLocale(locale)
        )
));
```

If you need to access to the Gettext provider outside your Vue files, you also can move the init in a dedicated module:

In `gettext-provider.ts`:
```ts
import { initVueGettext, getPOFileFromLocale } from "@tuleap/vue3-gettext-init";

export default initVueGettext(
    (locale: string) =>
        import(
            /* webpackChunkName: "some-app-po" */ "./po/" + getPOFileFromLocale(locale)
        )
);
```

In `main.ts`:
```ts
import { createApp } from "vue";
import gettext_provider from "./gettext-provider";

const app = createApp();
app.use(gettext_provider);
```

In some other JS files sharing the same PO file:
```ts
import { $gettext } from "./gettext-provider";

const some_translated_string = $gettext("String");
```
