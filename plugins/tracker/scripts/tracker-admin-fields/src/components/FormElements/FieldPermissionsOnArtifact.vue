<!--
  - Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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
  -->

<template>
    <div class="tlp-form-element">
        <label-for-field v-bind:field="field" />
        <div class="tlp-form-element">
            <label class="tlp-label tlp-checkbox">
                <input
                    type="checkbox"
                    v-bind:checked="are_permissions_defined"
                    v-on:click="onClick"
                />
                {{ $gettext("Restrict access to this artifact for the following user groups:") }}
            </label>
        </div>
        <div
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': !are_permissions_defined }"
        >
            <select
                ref="select_element"
                class="tlp-select"
                v-bind:disabled="!are_permissions_defined"
                multiple
            >
                <option
                    v-for="value in field.values.ugroup_representations"
                    v-bind:key="value.id"
                    v-bind:value="value.id"
                >
                    {{ value.label }}
                </option>
            </select>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { createListPicker } from "@tuleap/list-picker";
import type { PermissionsOnArtifactFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import LabelForField from "./LabelForField.vue";

const { $gettext } = useGettext();

const props = defineProps<{
    field: PermissionsOnArtifactFieldStructure;
}>();

const select_element = ref<HTMLSelectElement | undefined>();
const are_permissions_defined = ref(props.field.values.is_used_by_default);

const resetSelectedOption = (): void => {
    if (!select_element.value) {
        return;
    }

    for (const selected_option of select_element.value.options) {
        selected_option.selected = false;
    }
};

const onClick = (): void => {
    are_permissions_defined.value = !are_permissions_defined.value;

    if (!are_permissions_defined.value) {
        resetSelectedOption();
    }
};

onMounted(() => {
    if (!select_element.value) {
        return;
    }

    createListPicker(select_element.value, {
        placeholder: $gettext("Please select user groups"),
    });
});
</script>
