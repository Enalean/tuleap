# i18n in Vue apps

We use Gettext with
[vue-gettext](https://github.com/Polyconseil/vue-gettext) to translate
strings in our Vue apps. Here is some advice specific to `vue-gettext`:

## Always add a `key` attribute when you have conditional translated strings

For example, DO NOT do this:

``` html
// TranslatedExample.vue
<template>
    <p v-translate v-if="a_condition">First translated string</p>
    <p v-translate v-else>This string will never be shown</p> <!-- <== This string will NEVER be shown -->
</template>
```

The string in the `v-else` will never be shown, because Vue does not
know that the two `<p>` tags actually have different content. Vue tries
to limit the number of DOM changes, so it will only change attributes
and will change the text node, which will mess up `vue-gettext`. See
[the Vue.js documentation on the key
attribute](https://v2.vuejs.org/v2/api/#key) for details.

INSTEAD, DO THIS:

``` html
// TranslatedExample.vue
<template>
    <p v-translate v-if="a_condition" key="first_case">First translated string</p>
    <p v-translate v-else key="other_case">This string will be shown</p>
</template>
```

## Never use Vue.js interpolation inside translated strings

For example, DO NOT do this:

``` html
// TranslatedExample.vue
<template>
    <p v-translate>Current value: {{ reactive_value }}</p>
    <translate>Current value: {{ reactive_value }}</translate>
</template>
```

This will break the translation. The string will always show in English,
never in translated languages. Always use
`v-bind:translate-params="{ params }"` or `v-translate="{ params }"`
with `%{ param }` in the translated string. See [vue-gettext\'s
syntax](https://github.com/Polyconseil/vue-gettext#custom-parameters)

INSTEAD, DO THIS:

``` html
// TranslatedExample.vue
<template>
   <p v-translate="{ reactive_value }">Current value: %{ reactive_value }</p>
   <translate v-bind:translate-params="{ reactive_value }">Current value: %{ reactive_value }</translate>
</template>
```

## Never use `v-bind` on attributes in HTML tags in translated strings

For example, DO NOT do this:

``` html
// TranslatedExample.vue
<template>
    <p v-translate><a v-bind:href="link_url">{{ link_text }}</a> has done some changes in this document.</p>
</template>
```

This will break reactivity. If `link_url` or `link_text` change value,
the text will not change. See [vue-gettext\'s doc about
this](https://github.com/Polyconseil/vue-gettext#caveat-when-using-v-translate-with-vue-components-or-vue-specific-attributes).

INSTEAD, DO THIS:

``` html
// TranslatedExample.vue
<template>
    <p v-translate="{ link_url, link_text }"><a href="%{ link_url }">%{ link_text }</a> has done some changes in this document.</p>
</template>
```

## Name your parameter when your translations have parameters

For example, DO NOT do this:

``` html
// TranslatedExample.vue
<template>
    <p v-bind:translate-params="vue_variable_for_nb">%{ vue_variable_for_nb } changes have been done in this document.</p>
</template>
```

If your vue variable is updated, then you won\'t have to update the
corresponding translation.

INSTEAD, DO THIS:

``` html
// TranslatedExample.vue
<template>
    <p v-bind:translate-params="{nb: vue_variable_for_nb}">%{ nb } changes have been done in this document.</p>
</template>
```

## Resources

-   vue-gettext: <https://github.com/Polyconseil/vue-gettext>
