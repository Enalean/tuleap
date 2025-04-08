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
    <select ref="field_selector" multiple>
        <option></option>
        <option
            v-for="field in available_fields"
            v-bind:key="field.field_id"
            v-bind:value="field.label"
            data-test="available-readonly-fields"
            disabled
        >
            {{ field.label }}
        </option>
    </select>

    <table class="tlp-table" data-test="artidoc-configuration-fields-table">
        <thead>
            <tr>
                <th></th>
                <th>{{ $gettext("Field") }}</th>
                <th>{{ $gettext("Display") }}</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody v-if="selected_fields.length === 0">
            <tr>
                <td
                    colspan="5"
                    class="tlp-table-cell-empty"
                    data-test="readonly-fields-empty-state"
                >
                    {{ $gettext("No fields selected") }}
                </td>
            </tr>
        </tbody>
        <tbody v-else>
            <tr
                v-for="(field, index) in selected_fields"
                v-bind:key="index"
                data-test="readonly-field-rows"
            >
                <td></td>
                <td>{{ field.label }}</td>
                <td>
                    <label class="tlp-label tlp-checkbox">
                        <input
                            disabled
                            type="checkbox"
                            value="1"
                            v-bind:checked="field.display_type === 'block'"
                        />
                        {{ $gettext("Full row") }}
                    </label>
                </td>
                <td class="tlp-table-cell-actions">
                    <button
                        disabled
                        type="button"
                        class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline"
                    >
                        <i class="tlp-button-icon fa-solid fa-trash fa-fw" aria-hidden="true"></i>
                        {{ $gettext("Remove") }}
                    </button>
                </td>
                <td></td>
            </tr>
        </tbody>
    </table>
</template>

<script setup lang="ts">
import type { Ref } from "vue";
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useGettext } from "vue3-gettext";
import { createListPicker } from "@tuleap/list-picker";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import type { ListPicker } from "@tuleap/list-picker";

const { $gettext } = useGettext();

const props = defineProps<{
    selected_fields: ConfigurationField[];
    available_fields: ConfigurationField[];
}>();

const selected_fields: Ref<ConfigurationField[]> = ref(props.selected_fields);
const list_picker = ref<ListPicker | undefined>();
const field_selector = ref<HTMLSelectElement>();

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
.tlp-table {
    margin-top: var(--tlp-medium-spacing);
}
</style>
