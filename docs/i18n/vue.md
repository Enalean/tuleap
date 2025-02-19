# Internationalization in Vue apps

We use Gettext with [vue3-gettext][0] to translate strings in our Vue apps. Here is some advice specific to `vue-gettext`:

## Never use directives and components to translate strings

DO NOT do this:
```html
<template>
    <translate>First translated string</translate> <!-- This is forbidden -->
    <p v-translate>Second translated string</p> <!-- This is forbidden too -->
</template>
```

Do this instead:
```html
<template>
    {{ $gettext("First translated string") }}
    {{ $gettext("Second translated string") }}
</template>
```

## Name your parameter when your translations have parameters

For example, DO NOT do this:

```html
// TranslatedExample.vue
<template>
    {{ $gettext("%{ vue_variable_for_nb } changes have been done in this document.", vue_variable_for_nb) }}
</template>
```

If your vue variable is updated, then you must update the corresponding translation.

INSTEAD, DO THIS:

```html
// TranslatedExample.vue
<template>
    {{ $gettext("%{ nb } changes have been done in this document.", { nb: vue_variable_for_nb }) }}
</template>
```

## Resources

* [vue3-gettext][0]

[0]: https://github.com/jshmrtn/vue3-gettext
