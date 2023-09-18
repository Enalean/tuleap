<!--
  - Copyright (c) 2023-Present Enalean
  -
  - Permission is hereby granted, free of charge, to any person obtaining a copy
  - of this software and associated documentation files (the "Software"), to deal
  - in the Software without restriction, including without limitation the rights
  - to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  - copies of the Software, and to permit persons to whom the Software is
  - furnished to do so, subject to the following conditions:
  -
  - The above copyright notice and this permission notice shall be included in all
  - copies or substantial portions of the Software.
  -
  - THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  - IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  - FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  - AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  - LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  - OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  - SOFTWARE.
  -->

<template>
    <a
        v-bind:href="sanitized_href"
        v-bind:aria-label="label"
        class="project-sidebar-nav-promoted-item"
        v-bind:title="description"
        data-test="project-sidebar-tool-promoted-item"
    >
        <span class="project-sidebar-nav-promoted-item-label">{{ label }}</span>
    </a>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { sanitizeURL } from "../url-sanitizer";

// We cannot directly import the Tool interface from the external file so we duplicate the content for now
// See https://github.com/vuejs/vue-next/issues/4294
const props = defineProps<{
    href: string;
    label: string;
    description: string;
}>();
const sanitized_href = computed(() => sanitizeURL(props.href));
</script>
