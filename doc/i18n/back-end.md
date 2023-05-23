# i18n in the Back-end

All new work should use gettext for translation. Translation using
`.tab` files should only exist in the legacy code and will be replaced
by gettext.

[Gettext system documentation](https://www.gnu.org/software/gettext/)

## In core

``` php
echo _('Homepage');
```

## In plugins

We use the *domain* feature provided by gettext in order to have i18n in
plugins.

``` php
echo dgettext('tuleap-myplugin', 'File');
```

The command `xgettext` extracts strings without being able to interpret
PHP constants or variables. Don\'t try to be too smart and don\'t put
the domain `tuleap-myplugin` in a variable or a constant, we **need** to
repeat ourselves.

You can use localized strings from core or other plugins (beware of
dependencies!) in a given plugin.

## In REST routes

When error messages have functional meaning that cannot be deducted by
the client (for example an error message explaining why a tracker expert
query is invalid), then they should be translated with the following
pattern:

``` php
throw new I18NRestException(
     400,
     sprintf(
         dgettext('tuleap-taskboard', "Could not find artifact to add with id %d."),
         $id
     )
 );
```

## Pluralization

``` php
echo sprintf(
    dngettext(
        'tuleap-tracker',
        "The field '%s' doesn't exist",
        "The fields '%s' don't exist",
        $nb_nonexistent_fields
    ),
    implode("', '", $nonexistent_fields)
);
// The field 'summary' doesn't exist
// or
// The fields 'summary', 'details' don't exist
```

Do not concatenate strings to build a sentence! For example
`nb + " pull requests"` or `"It is " + "suspended"` are not allowed.

## Workflow

1.  Add a new localizable string.
2.  Run `make generate-po`. This will update corresponding .pot files
    that are templates for your localization files.
3.  Edit localization files in
    `site-content/fr_FR/LC_MESSAGES/tuleap-xxxx.po` with your favorite
    editor (poedit is fine).
4.  Once you have localized your sentences, run `make generate-mo` (some
    editors, like poedit, generate .mo files for you). You may need to
    restart your webserver
    (`docker exec tuleap-web systemctl restart tuleap-php-fpm`).
5.  Refresh your browser, and voilà!

If you are introducing gettext in a plugin, you must
`mkdir plugins/<name>/site-content/fr_FR` before calling
`make generate-po`.

Furthermore you must declare your domain in the constructor of your
plugin. For example, for `tracker` plugin, in `trackerPlugin.class.php`:

``` php
bindtextdomain('tuleap-tracker', __DIR__.'/../site-content');
```

On our dev setup (tuleap-aio-dev) you must ensure that \"fr_FR\" locale
is installed (`locale -a`). If it is not the case, run
`localedef -i fr_FR -f UTF-8 fr_FR.UTF-8`.

## tab files

This system is based on a key/value pair. PHP code references a key
(actually a primary and a secondary keys) which is replaced by the full
sentence, according to the user preferences.

Language files are available in the `site-content/` directory, for
example `site-content/en_US/include/include.tab`. The same file exists
for the french version: `site-content/fr_FR/include/include.tab`.

These language files follow a defined syntax:

``` bash
key1 [tab] key2 [tab] translated string
```

and sentences are separated by a carriage return. Keys are split in
different files for convenience, but are \"compiled\" in a big unique
file at execution.

Example:

``` bash
include_exit    error   An error occured
```

The class that manages i18n is BaseLanguage
(`src/common/language/BaseLanguage.class.php`). It is initialized by
`pre.php`, and language is set according to the user preferences. This
php code will return the matching string defined in language files:

``` php
$GLOBALS['Language']->getText('include_exit', 'error'));
```

## Cache

For performance reasons, Tuleap localization is kept in a cache file.
When you are done adding / editing .tab files, connect to your `web`
container and run the following command to clear this cache and see your
modifications:

``` bash
$ tuleap --clear-caches
```
