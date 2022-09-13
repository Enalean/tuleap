<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <div class="switch-to-item-entry">
        <div
            class="switch-to-item-entry-with-links"
            v-bind:class="{ 'switch-to-item-entry-with-links-with-badge': entry.xref }"
        >
            <a
                v-bind:href="entry.html_url"
                v-bind:class="entry.color_name"
                class="switch-to-item-entry-link"
                ref="entry_link"
                data-test="entry-link"
                v-on:keydown="changeFocus"
            >
                <i
                    class="fa fa-fw switch-to-item-entry-icon"
                    v-bind:class="entry.icon_name"
                    aria-hidden="true"
                ></i>
                <span
                    class="switch-to-item-entry-badge cross-ref-badge cross-ref-badge-on-dark-background"
                    v-bind:class="xref_color"
                    v-if="entry.xref"
                >
                    <highlight-matching-text>{{ entry.xref }}</highlight-matching-text>
                </span>
                <highlight-matching-text class="switch-to-item-entry-label">
                    {{ entry.title }}
                </highlight-matching-text>
            </a>
            <div class="switch-to-item-entry-quick-links" v-if="has_quick_links">
                <quick-link
                    v-for="link of entry.quick_links"
                    v-bind:key="link.html_url"
                    class="switch-to-item-entry-quick-links-link"
                    v-bind:link="link"
                    v-bind:item="entry"
                    v-bind:project="null"
                />
            </div>
        </div>
        <span class="switch-to-item-project">
            {{ entry.project.label }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import type { ItemDefinition } from "../../../type";
import HighlightMatchingText from "../HighlightMatchingText.vue";
import type { FocusFromItemPayload } from "../../../stores/type";
import { useKeyboardNavigationStore } from "../../../stores/keyboard-navigation";
import { storeToRefs } from "pinia";
import QuickLink from "../QuickLink.vue";

const props = defineProps<{
    entry: ItemDefinition;
    changeFocusCallback: (payload: FocusFromItemPayload) => void;
}>();

const xref_color = ref<string>("tlp-swatch-" + props.entry.color_name);
const has_quick_links = ref<boolean>(props.entry.quick_links.length > 0);

const entry_link = ref<HTMLAnchorElement | null>(null);

const navigation_store = useKeyboardNavigationStore();
const { programmatically_focused_element } = storeToRefs(navigation_store);

watch(programmatically_focused_element, () => {
    if (programmatically_focused_element.value !== props.entry) {
        return;
    }

    if (entry_link.value instanceof HTMLAnchorElement) {
        entry_link.value.focus();
    }
});

function changeFocus(event: KeyboardEvent): void {
    switch (event.key) {
        case "ArrowUp":
        case "ArrowRight":
        case "ArrowDown":
        case "ArrowLeft":
            event.preventDefault();
            props.changeFocusCallback({ entry: props.entry, key: event.key });
            break;
        default:
    }
}
</script>
