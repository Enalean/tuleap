<!--
  - Copyright (c) 2021-Present Enalean
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
        class="project-sidebar-nav-item"
        v-bind:class="{
            active: is_tool_active,
            'project-sidebar-nav-item-with-promoted-items': has_promoted_items,
        }"
        v-bind:title="description"
        v-bind:target="open_in_new_tab ? '_blank' : '_self'"
        v-bind:rel="open_in_new_tab ? 'noopener noreferrer' : ''"
        v-bind:data-shortcut-sidebar="shortcut"
        data-test="project-sidebar-tool"
    >
        <i
            class="project-sidebar-nav-item-icon"
            aria-hidden="true"
            v-bind:class="icon"
            data-test="tool-icon"
        ></i>
        <span class="project-sidebar-nav-item-label">{{ label }}</span>
        <span
            v-if="has_tooltip"
            class="project-sidebar-nav-item-info-tooltip"
            v-bind:title="tooltip"
        >
            <i class="fa-solid fa-circle-question" role="img" v-bind:aria-label="tooltip"></i>
        </span>
        <i
            v-if="open_in_new_tab"
            class="fa-solid fa-arrow-right project-sidebar-nav-item-new-tab"
            aria-hidden="true"
            data-test="tool-new-tab-icon"
        ></i>
    </a>
    <nav v-if="has_promoted_items" class="project-sidebar-promoted-item-nav">
        <promoted-item-presenter
            v-for="item in promoted_items"
            v-bind="item"
            v-bind:key="href + label + description + item.href + item.label + item.description"
        />
    </nav>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { sanitizeURL } from "../url-sanitizer";
import type { PromotedItem } from "../configuration";
import PromotedItemPresenter from "./PromotedItemPresenter.vue";

// We cannot directly import the Tool interface from the external file so we duplicate the content for now
// See https://github.com/vuejs/vue-next/issues/4294
const props = defineProps<{
    href: string;
    label: string;
    description: string;
    icon: string;
    open_in_new_tab: boolean;
    is_active: boolean;
    shortcut_id: string;
    promoted_items?: ReadonlyArray<PromotedItem>;
    info_tooltip?: string;
}>();
const sanitized_href = computed(() => sanitizeURL(props.href));
const shortcut = computed(() => `sidebar-${props.shortcut_id}`);
const has_promoted_items = computed(() => props.promoted_items && props.promoted_items.length > 0);
const is_tool_active = computed(
    () => props.is_active && !props.promoted_items?.some((item) => item.is_active),
);
const tooltip = computed(() => props.info_tooltip || "");
const has_tooltip = computed(() => tooltip.value !== "");
</script>
