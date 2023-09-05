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
    <input
        id="switch-to-filter"
        type="search"
        name="words"
        v-bind:placeholder="$gettext('Project, recent item, …')"
        v-bind:value="filter_value"
        v-on:keyup="update"
        autocomplete="off"
        data-test="switch-to-filter"
        ref="input"
    />
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import { useRootStore } from "../../stores/root";
import { storeToRefs } from "pinia";
import { useKeyboardNavigationStore } from "../../stores/keyboard-navigation";

const props = defineProps<{ modal: Modal | null }>();
const store = useRootStore();
const keyboard_navigation = useKeyboardNavigationStore();

const input = ref<HTMLInputElement | null>(null);
const { programmatically_focused_element } = storeToRefs(keyboard_navigation);
watch(programmatically_focused_element, () => {
    if (programmatically_focused_element.value !== null) {
        return;
    }

    if (input.value instanceof HTMLInputElement) {
        input.value.focus();
    }
});

const { filter_value } = storeToRefs(store);

onMounted((): void => {
    listenToHideModalEvent();
});

function listenToHideModalEvent(): void {
    if (props.modal) {
        props.modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, clearInput);
    }
}

watch(
    () => props.modal,
    () => {
        listenToHideModalEvent();
    },
);

onUnmounted((): void => {
    if (props.modal) {
        props.modal.removeEventListener(EVENT_TLP_MODAL_HIDDEN, clearInput);
    }
});

function clearInput(): void {
    if (filter_value.value !== "") {
        store.updateFilterValue("");
    }
}

function update(event: KeyboardEvent): void {
    if (event.key === "Escape") {
        if (props.modal) {
            props.modal.hide();
        }
        clearInput();
        return;
    }

    if (event.key === "ArrowDown") {
        keyboard_navigation.changeFocusFromFilterInput();
        return;
    }

    if (event.target instanceof HTMLInputElement) {
        store.updateFilterValue(event.target.value);
    }
}
</script>
