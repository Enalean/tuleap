<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    <a
        v-bind:href="link.html_url"
        v-bind:title="link.name"
        ref="link_element"
        v-on:keydown="changeFocus"
    >
        <i v-bind:class="link.icon_name" aria-hidden="true"></i>
    </a>
</template>

<script setup lang="ts">
import type { ItemDefinition, Project, QuickLink } from "../../type";
import { useKeyboardNavigationStore } from "../../stores/keyboard-navigation";
import { storeToRefs } from "pinia";
import { ref, watch } from "vue";

const props = defineProps<{
    link: QuickLink;
    project: Project | null;
    item: ItemDefinition | null;
}>();

const navigation_store = useKeyboardNavigationStore();
const { programmatically_focused_element } = storeToRefs(navigation_store);
const link_element = ref<HTMLAnchorElement | null>(null);

watch(programmatically_focused_element, () => {
    if (programmatically_focused_element.value !== props.link) {
        return;
    }

    if (link_element.value instanceof HTMLAnchorElement) {
        link_element.value.focus();
    }
});

function changeFocus(event: KeyboardEvent): void {
    switch (event.key) {
        case "ArrowUp":
        case "ArrowRight":
        case "ArrowDown":
        case "ArrowLeft":
            event.preventDefault();
            navigation_store.changeFocusFromQuickLink({
                item: props.item,
                project: props.project,
                quick_link: props.link,
                key: event.key,
            });
            break;
        default:
    }
}
</script>
