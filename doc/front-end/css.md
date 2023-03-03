# CSS

Tuleap uses [Sass](https://sass-lang.com/documentation) for its CSS
generation.

SCSS files are just extended CSS files. It means you can use variables,
functions, operations and more in CSS files very easily. It's fully
backward compatible with exiting CSS files (you can rename file.css to
file.scss, compile file.scss and it'll just work).

Please refer to the [Sass
documentation](https://sass-lang.com/documentation) for more
information.

SCSS files should always go in "themes" folders.

-   For the core, it should go in `src/themes/BurningParrot/css/`
-   For plugins, it should go in
    `plugins/<my-plugin>/themes/BurningParrot/`

## Compile SCSS files

From the root directory of the Tuleap sources:

``` bash
$ pnpm install
$ pnpm run build
```

This command will compile all SCSS files present in `plugin` and `src`
directories.

-   you have to run `pnpm run build` every time you edit a SCSS file.
-   CSS files will be git-ignored. Don't edit them manually.

If you are working in Tuleap "core", change your current directory to
`src/` to run the "pnpm" commands. If you are working in a plugin for
Tuleap, change your current directory to the "root" of that plugin in
`plugins/<my-plugin>/` to run the "pnpm" commands.

While you are working, the following command should help you:

``` bash
$ pnpm run watch
```

It will automatically rebuild CSS after changes.

## Best practices for Tuleap

When you submit a patch for review, we may request changes to better
match the following best practices. Please try to follow them.

### Files best practices

-   Never use the `style` HTML attribute.
-   Always use a SCSS file. No `<style>` tags.
-   Split your SCSS files into multiple
    [partials](https://sass-lang.com/documentation/at-rules/import#partials)
    files. Smaller files are easier to understand and review. You can
    then `@use` them from your main SCSS file.
-   File names for
    [partials](https://sass-lang.com/documentation/at-rules/import#partials)
    should always start with an "underscore" character, for example:
    `_colors.scss`.
-   When you prepend a Copyright block at the beginning of a SCSS file,
    never use the `/*!` style of comments. Those comments are output in
    compressed CSS. This makes them a lot larger for no benefit, because
    all included files will each add 30 lines of copyright to the final
    CSS file. Always use `/**`. See the [Sass documentation on
    comments](https://sass-lang.com/documentation/syntax/comments).

### Rules best practices

-   Prefer class to ID as a selector. Classes have lower
    [specificity](https://specificity.keegan.st/), so when someone
    really needs to override the style you had set, they don't have to
    create crazy rules or worse, use `!important`.

-   For the same reason, don't use `!important`. It's impossible to
    override.

-   Always prefix class names by the plugin (or the general view) you
    are in. For example, when working in the Git plugin, prefix all
    class names with `git-`

-   Use naming to indicate where the selector is. For example:

    ``` html
    <div class="git-repository-list">
        <section class="git-repository-card">
            <div class="git-repository-card-header">
            <!-- ... -->
    ```

    The long names help us avoid name-clashing with another plugin and
    help get a sense of where the rule is applied when reading Sass
    files.

-   Don't use the [descendant
    combinator](https://developer.mozilla.org/en-US/docs/Web/CSS/Descendant_combinator),
    for example `.class1 .class2`. It hurts performances because when
    the browser gets to "class2", it will have to recursively find all
    its ancestors to see if they are "class1".

-   For the same performance reason, if possible avoid using the [child
    combinator](https://developer.mozilla.org/en-US/docs/Web/CSS/Child_combinator),
    for example `.class1 > .class2`.

-   Instead, use a single specific class name that targets precisely
    what you want.

-   Always make sure the rules you are using work on our list of
    [supported browsers](https://docs.tuleap.org/user-guide/misc.html#user-supported-browsers). To do that you can check with the [Can I
    use](https://caniuse.com/) website.

#### Resources

-   [Sass documentation](https://sass-lang.com/documentation)
-   A tool to help you calculate CSS specificity:
    <https://specificity.keegan.st/>
-   Can I use, to check what is available for major browsers:
    <https://caniuse.com/>
