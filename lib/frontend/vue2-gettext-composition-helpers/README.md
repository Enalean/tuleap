# @tuleap/vue2-gettext-composition-helper

Provides helpers to use [vue-gettext](https://github.com/Polyconseil/vue-gettext) with the
[composition API](https://vuejs.org/guide/extras/composition-api-faq.html) in Vue 2.

The exposed API is similar to the one used by [jshmrtn/vue3-gettext](https://github.com/jshmrtn/vue3-gettext) to ease
future migration to Vue 3.

## Example

```ts
const gettext_provider = useGettext();

const translated = gettext_provider.$ngettext("%{ n } foo", "%{ n } foos", n);
const interpolated = gettext_provider.interpolate(translated, { n: n });
```
