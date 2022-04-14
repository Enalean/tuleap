# @tuleap/vue2-gettext-init

## Purpose

Instantiate [`vue-gettext`](https://github.com/Polyconseil/vue-gettext) with the appropriate translations for the current Tuleap user.

## Usage

```ts
import Vue from "vue";
import { initVueGettext, getPOFileFromLocale } from "@tuleap/vue2-gettext-init";

await initVueGettext(
    Vue,
    (locale: string) =>
        import(
            /* webpackChunkName: "some-app-po-" */ "./po/" + getPOFileFromLocale(locale)
        )
);
```
