# Internationalization (i18n)

I18n in Tuleap should always be done with
[Gettext](https://www.gnu.org/software/gettext/).

To extract translated strings and add them to the `.po` files for
translation, you should always run the following command:

```
$ make generate-po
```

This will parse all Tuleap code (core and plugin). If you want to limit
the work to a plugin (to speed up the extraction):

```
$ PLUGIN=git make generate-po
```

Then `.po` files can be edited with the editor of your choice. We mostly
use [Poedit](https://poedit.net/) which helps to remove obsolete
translations and add checks (like translation that should end with same
punctuation mark than source string for example).

Contributed files should not contain:

-   fuzzy strings
-   obsolete strings

## Supported languages

The list of currently available languages can be seen here:
[available-languages](https://docs.tuleap.org/user-guide/user/preferences.html#languages)

Contribution should respect `.po` files structure. Each component in
Tuleap has its very own `.po` files and should be contributed as is.
Merging of `.po` files for contribution is not supported.

### Adding a new language

Your language is not in the list? Contributions are most welcome!

In short, the process is the following (example for Brazilian Portuguese
`pt_BR`):

1.  If it is not already done, [Clone Tuleap Sources](./quick-start/clone-tuleap.md)

2.  Create folder for your locale:

    ``` bash
    mkdir -p site-content/pt_BR/LC_MESSAGES/
    ```

3.  Define how the language should appear in the user preferences
    selection:

    ``` bash
    echo -e "system\tlocale_label\tPortuguês brasileiro" > /etc/tuleap/site-content/pt_BR/pt_BR.tab
    # if you have a running environment you will need to clear the cache in the container
    # with `tuleap --clear-cache` to take into account the new entry
    ```

4.  Translate

    1.  Copy a `.pot` template into your language. A good start is
        Tuleap core.

        ``` bash
        make generate-po # This is needed to create up to date .pot templates
        cp site-content/tuleap-core.pot site-content/pt_BR/LC_MESSAGES/
        ```

    2.  Edit the new file (don't forget about .po headers)

    3.  Repeat

5.  [push-code](./patches.md)


Please [contact us](https://tuleap.net/projects/tuleap) beforehand to
make sure that there isn't already an ongoing contribution in the same
language by someone else.

We plan to use an external system like Crowdin or Weblate to ease
translation contributions, but no progress has been made on this
subject. Stay tuned!

## Development specificities

Depending on where you are in Tuleap code, you should follow
recommendations to translate your strings:

* [Back end](./i18n/back-end.md)
* [Mustache](./i18n/mustache.md)
* [Vue](./i18n/vue.md)

## Resources

-   [Gettext system
    documentation](https://www.gnu.org/software/gettext/)
-   [Poedit](https://poedit.net/)
