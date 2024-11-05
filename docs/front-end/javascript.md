# Javascript

New code must be written in
[TypeScript](https://www.typescriptlang.org).

Typescript code should always go in \"scripts\" folders.

-   For the core, it should go in `src/scripts/`
-   For plugins, it should go in `plugins/<my-plugin>/scripts/`

## Build the typescript files

From the root directory of the Tuleap sources (you must have pnpm
installed):

``` bash
$ pnpm install
$ pnpm run build
```

This command will install the tools needed, transpile the javascript to
make it compatible with our
[supported browsers](https://docs.tuleap.org/user-guide/misc.html#which-browser-should-i-use-to-browse-tuleap) and minify (compress) it.

-   you have to run `pnpm run build` every time you edit a Javascript
    file.
-   Built javascript files go to the `frontend-assets` folder. You
    should never modify files in `frontend-assets` folders. Your
    modifications will be erased on the next build.

Tuleap has a lot of pages and many functionalities. To handle them,
there is an ongoing work to split the frontend in many small apps. Thus,
Tuleap core and some of the plugins contain several "applications".

If you are working in Tuleap "core", change your current directory to
`src/<application>` to run the pnpm commands. If you are working on a
plugin for Tuleap, change your current directory to
`plugins/<my-plugin>/scripts/<application>` to run the pnpm commands.

While you are working, the following commands should help you:

``` bash
$ pnpm run watch
$ pnpm test -- --watch
```

-   `pnpm run watch` will automatically rebuild Javascript after
    changes.
-   `pnpm test` will run the unit tests once. It is used by the
    Continuous integration to validate changes.
-   `pnpm test -- --watch` will run unit tests based on modified files
    since the last Git commit. See the command\'s built-in help.
-   `pnpm test -- --coverage` will run the unit tests and generate a
    coverage report.

## Best-practices for Tuleap

When you submit a patch for review, we may request changes to better
match the following best practices. Please try to follow them.

-   Always write new code in Typescript.
-   Always use a Typescript file. No manual `<script>` tags.
-   Whenever you need to run code when the page is loaded, do it like
    this:

``` typescript
/** main.ts */
document.addEventListener("DOMContentLoaded", (): void => {
    // Your initialization code
});
```

-   Always manipulate the DOM in only one place. For example when using
    [Vue](./vue.md), do not change datasets
    or class names in `.vue` files. Do it in `main.ts`
-   Leverage ES6 and later versions! Your code will be transpiled to
    keep it compatible with our supported browsers.
-   Always make sure the Browser APIs you are using (for example DOM,
    `Location`, `CustomEvent`, etc.) work on our list of
    [supported browsers](https://docs.tuleap.org/user-guide/misc.html#user-supported-browsers).
    To do that you can check with the [Can I use](https://caniuse.com/) website.

### Resources

-   Exploring ES6: <https://exploringjs.com/es6/index.html>
-   Can I use, to check what is available for major browsers:
    <https://caniuse.com/>
