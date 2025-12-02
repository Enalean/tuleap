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
        <label-for-field v-bind:id="id" v-bind:field="field" />
        <div class="tlp-form-element tlp-form-element-prepend">
            <span class="tlp-prepend">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
            </span>
            <input
                type="text"
                v-bind:id="id"
                class="tlp-input"
                v-bind:data-enabletime="enabletime"
                v-bind:size="size"
                ref="input"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, useTemplateRef } from "vue";
import type { EditableDateFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import LabelForField from "./LabelForField.vue";
import { createDatePicker, getLocaleWithDefault } from "@tuleap/tlp-date-picker";
import {
    DEFAULT_VALUE_DATE_TYPE_TODAY,
    DISPLAY_DATE_AND_TIME,
} from "@tuleap/plugin-tracker-constants";

const props = defineProps<{
    field: EditableDateFieldStructure;
}>();

const id = computed(() => "textarea-" + props.field.field_id);
const enabletime = computed(() =>
    props.field.specific_properties.display_time === DISPLAY_DATE_AND_TIME ? "true" : undefined,
);
const size = computed(() =>
    props.field.specific_properties.display_time === DISPLAY_DATE_AND_TIME ? 19 : 11,
);

const input = useTemplateRef<HTMLInputElement>("input");

onMounted(() => {
    if (input.value === null) {
        return;
    }
    const locale = getLocaleWithDefault(document);
    createDatePicker(input.value, locale, getOptionsForDatePicker(props.field));
});

function getOptionsForDatePicker(
    field: EditableDateFieldStructure,
): Parameters<typeof createDatePicker>[2] {
    if (field.specific_properties.default_value_type === DEFAULT_VALUE_DATE_TYPE_TODAY) {
        return {
            defaultDate: new Date(),
        };
    }

    if (field.specific_properties.default_value > 0) {
        return {
            defaultDate: field.specific_properties.default_value * 1000,
        };
    }

    return {};
}
</script>
