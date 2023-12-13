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
    <div
        class="project-sidebar-nav-promoted-item"
        v-bind:class="{
            active: is_item_active,
        }"
        v-on:click="goToItem"
        ref="root"
    >
        <a
            v-bind:href="sanitized_href"
            v-bind:aria-label="label"
            v-bind:title="description"
            class="project-sidebar-nav-promoted-item-link project-sidebar-nav-promoted-item-label"
        >
            {{ label }}
        </a>
        <a
            v-if="quick_link_add"
            v-bind:href="sanitized_quick_link_add_href"
            v-bind:aria-label="quick_link_add.label"
            class="project-sidebar-nav-promoted-item-quick-link"
        >
            <i role="img" class="fa-solid fa-plus"></i>
        </a>
    </div>
    <nav v-if="has_items" class="project-sidebar-subitem-nav">
        <sub-item-presenter
            v-for="item in items"
            v-bind="item"
            v-bind:key="href + label + description + item.href + item.label + item.description"
        />
    </nav>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { sanitizeURL } from "../url-sanitizer";
import type { Item, QuickLink } from "../configuration";
import SubItemPresenter from "./SubItemPresenter.vue";

// We cannot directly import the Tool interface from the external file so we duplicate the content for now
// See https://github.com/vuejs/vue-next/issues/4294
const props = defineProps<{
    href: string;
    label: string;
    description: string;
    is_active: boolean;
    quick_link_add?: QuickLink | null;
    items?: ReadonlyArray<Item>;
}>();
const sanitized_href = computed(() => sanitizeURL(props.href));
const sanitized_quick_link_add_href = computed(() =>
    props.quick_link_add ? sanitizeURL(props.quick_link_add.href) : "",
);
const has_items = computed(() => props.items && props.items.length > 0);
const is_item_active = computed(
    () => props.is_active && !props.items?.some((item) => item.is_active),
);
const root = ref<HTMLInputElement | null>(null);

function goToItem(event: MouseEvent): void {
    if (root.value && event.target === root.value) {
        window.location.href = props.href;
    }
}
</script>
