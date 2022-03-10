# @tuleap/vue3-gettext-init

## Purpose

Instantiate [`vue3-gettext`](https://github.com/jshmrtn/vue3-gettext) with the appropriate translation for the current
Tuleap user.

## Usage

```ts
import { createApp } from "vue";
import { initVueGettext, getPOFileFromLocale } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";

const app = createApp();
app.use(await initVueGettext(
    createGettext,
    (locale: string) =>
        import(
            /* webpackChunkName: "some-app-po" */ "./po/" + getPOFileFromLocale(locale)
        )
));
```
