# Locale

Common utility functions and types to handle locale strings like `en_US` or `fr_FR`.

It provides a stricter type `LocaleString` to represent locale codes. Locale string is understood in "Tuleap PHP" format (with underscore separator `_`, NOT dash `-`).

`getLocaleWithDefault()` is a function to read a `LocaleString` from the attribute `data-user-locale` on the document `<body>` tag. If the attribute is not present or has an invalid value, it defaults to `en_US`.

It also provides utility functions to construct a `.po` file name from a locale string: `getPOFileFromLocale(locale)` is for Webpack, `getPOFileFromLocaleWithoutExtension(locale)` is for Vite.

It also provides `LocaleString` constants. They are mainly useful for tests.
