<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <select ref="field_selector" v-on:change="selectField" multiple>
        <option></option>
        <option
            v-for="field in currently_available_fields"
            v-bind:key="field.field_id"
            v-bind:value="field.field_id"
            data-test="available-readonly-fields"
        >
            {{ field.label }}
        </option>
    </select>

    <div data-test="artidoc-configuration-fields-table">
        <div class="fields-selection-header">
            <div class="header-dnd-handle"></div>
            <div class="header-field">{{ $gettext("Field") }}</div>
            <div class="header-display-type">{{ $gettext("Display") }}</div>
            <div class="header-actions"></div>
            <div class="header-reorder-arrows"></div>
        </div>
        <div
            v-if="currently_selected_fields.length === 0"
            class="fields-selection-empty-state"
            data-test="readonly-fields-empty-state"
        >
            {{ $gettext("No fields selected") }}
        </div>
        <selected-fields-list
            v-else
            v-bind:currently_selected_fields="currently_selected_fields"
            v-bind:fields_reorderer="fields_reorderer"
            v-on:unselect-field="unselectField"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useGettext } from "vue3-gettext";
import { createListPicker } from "@tuleap/list-picker";
import type { ListPicker } from "@tuleap/list-picker";
import { buildFieldsReorderer } from "@/sections/readonly-fields/FieldsReorderer";
import SelectedFieldsList from "@/components/configuration/SelectedFieldsList.vue";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";

const { $gettext } = useGettext();

const props = defineProps<{
    selected_fields: ConfigurationField[];
    available_fields: ConfigurationField[];
}>();

const currently_selected_fields = ref<ConfigurationField[]>(props.selected_fields);
const currently_available_fields = ref<ConfigurationField[]>(props.available_fields);
const fields_reorderer = buildFieldsReorderer(currently_selected_fields);

const list_picker = ref<ListPicker | undefined>();
const field_selector = ref<HTMLSelectElement>();

function selectField(event: Event): void {
    if (!(event.target instanceof HTMLSelectElement)) {
        return;
    }

    const field_id = Number.parseInt(event.target.selectedOptions[0].value, 10);
    const field_index = currently_available_fields.value.findIndex(
        (field) => field_id === field.field_id,
    );
    const field = currently_available_fields.value[field_index];

    currently_available_fields.value.splice(field_index, 1);
    currently_selected_fields.value.push(field);
}

function unselectField(field: ConfigurationField): void {
    const field_index = currently_selected_fields.value.indexOf(field);

    currently_selected_fields.value.splice(field_index, 1);
    currently_available_fields.value.push(field);
}

onMounted(() => {
    if (!(field_selector.value instanceof HTMLSelectElement)) {
        return;
    }

    list_picker.value = createListPicker(field_selector.value, {
        locale: document.body.dataset.userLocale,
        placeholder: $gettext("Select fields..."),
        is_filterable: true,
    });
});

onBeforeUnmount(() => {
    list_picker.value?.destroy();
});
</script>

<style scoped lang="scss">
@use "@/themes/includes/size";

.fields-selection-header {
    display: flex;
    margin-top: var(--tlp-medium-spacing);
    border-bottom: 2px solid var(--tlp-main-color);
    color: var(--tlp-main-color);
    font-weight: 600;

    > div {
        padding: var(--tlp-small-spacing);
    }
}

.header-dnd-handle {
    width: size.$drag-and-drop-handle-width;
}

.header-field {
    flex: 1;
}

.header-actions {
    width: size.$fields-selection-action-button-column-width;
}

.header-display-type {
    width: size.$fields-selection-display-type-column-width;
}

.header-reorder-arrows {
    width: size.$reorder-arrow-size;
}

.fields-selection-empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100px;
    color: var(--tlp-dimmed-color);
    font-style: italic;
}
</style>
