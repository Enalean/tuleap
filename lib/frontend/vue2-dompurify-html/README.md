# @tuleap/vue2-dompurify-html

Minimal version of [vue-dompurify-html](https://github.com/LeSuisse/vue-dompurify-html/tree/main/packages/vue-dompurify-html)
for our remaining usages of Vue 2.

## Usage

```ts
import VueDOMPurifyHTML from '@tuleap/vue2-dompurify-html';
import Example from './Example.vue';
import Vue from 'vue';

Vue.use(VueDOMPurifyHTML);
const App = Vue.extend(Example);
new App().$mount('#app');
```

```vue
<template>
    <div>
        <h1>Directive</h1>
        <div v-dompurify-html="rawHtml">Expect text with color</div>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";

const rawHtml = ref('<span style="color: red">Hello!</span><img src=a onerror="alert(1)">');
</script>
```
