# @tuleap/po-gettext-plugin

## Purpose

Transform `.po` files during the build process so they can be used by JS app.

## Usage

### Vite

```ts
import POGettextPlugin from "@tuleap/po-gettext-plugin";

export default defineConfig({
    plugins: [POGettextPlugin.vite()],
});

```

### Webpack
```js
module.exports = [
    {
        // ...
        plugins: [
            require("@tuleap/po-gettext-plugin").default.webpack(),
        ],
    },
];
```
