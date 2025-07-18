<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <button
        v-if="!is_in_add_mode"
        type="button"
        class="taskboard-add-in-place-button tlp-button-primary tlp-button-outline"
        v-bind:class="button_class"
        v-bind:title="title"
        v-on:click="$emit('click')"
        data-test="add-in-place-button"
        data-navigation="add-form"
        ref="addButton"
    >
        <i class="fa" v-bind:class="icon_class" aria-hidden="true"></i>
        <span v-if="label">{{ label }}</span>
    </button>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, computed } from "vue";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const props = defineProps<{
    label: string;
    is_in_add_mode: boolean;
}>();

defineEmits<{
    (e: "click"): void;
}>();

const addButton = ref<HTMLElement | null>(null);
const is_in_add_mode_ref = ref(props.is_in_add_mode);

watch(is_in_add_mode_ref, (is_in_add_mode: boolean) => {
    if (!is_in_add_mode) {
        focusAddButton();
    }
});

const title = computed((): string => {
    return props.label || $gettext("Add new card");
});

const icon_class = computed((): string => {
    return props.label !== "" ? "fa-tlp-hierarchy-plus tlp-button-icon" : "fa-plus";
});

const button_class = computed((): string => {
    return props.label === "" ? "" : "tlp-button-small taskboard-add-in-place-button-with-label";
});

function focusAddButton(): void {
    nextTick(() => {
        addButton.value?.focus();
    });
}
</script>
