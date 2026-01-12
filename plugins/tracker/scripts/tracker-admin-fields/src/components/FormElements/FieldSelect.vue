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
  -->

<template>
    <div class="tlp-form-element">
        <label-for-field v-bind:field="field" />
        <select ref="select_element" class="tlp-select" v-bind:multiple="is_multiple_select">
            <select-box-options v-bind:field="field" />
        </select>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { createListPicker } from "@tuleap/list-picker";
import type { ListFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import { MULTI_SELECTBOX_FIELD } from "@tuleap/plugin-tracker-constants";
import LabelForField from "./LabelForField.vue";
import SelectBoxOptions from "./SelectBoxOptions/SelectBoxOptions.vue";
import { NONE_VALUE } from "../../helpers/list-field-value";

const props = defineProps<{
    field: ListFieldStructure;
}>();

const select_element = ref<HTMLSelectElement | undefined>();
const is_multiple_select = props.field.type === MULTI_SELECTBOX_FIELD;

onMounted(() => {
    if (!select_element.value) {
        return;
    }

    createListPicker(select_element.value, {
        is_filterable: true,
        none_value: !props.field.required ? NONE_VALUE : null,
    });
});
</script>
