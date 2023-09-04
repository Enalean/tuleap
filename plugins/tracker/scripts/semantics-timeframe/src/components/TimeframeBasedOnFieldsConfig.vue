<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="start-date">
                {{ $gettext("Start date") }}
                <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
            </label>

            <select
                id="start-date"
                name="start-date-field-id"
                class="tlp-select tlp-select-adjusted"
                data-test="start-date-field-select-box"
                v-model="user_selected_start_date_field_id"
                required
            >
                <option value="">{{ $gettext("Choose a field...") }}</option>
                <option
                    v-for="date_field in suitable_start_date_fields"
                    v-bind:value="date_field.id"
                    v-bind:name="date_field.label"
                    v-bind:key="'start-date-field' + date_field.id"
                >
                    {{ date_field.label }}
                </option>
            </select>
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="option-end-date">
                <input
                    id="option-end-date"
                    type="radio"
                    value="end-date"
                    data-test="option-end-date"
                    v-bind:checked="!is_in_start_date_duration_mode"
                    v-on:click="toggleTimeframeMode(MODE_START_DATE_END_DATE)"
                />
                {{ $gettext("End date") }}
                <i
                    class="fa-solid fa-asterisk"
                    aria-hidden="true"
                    data-test="end-date-field-highlight-field-required"
                    v-if="!is_in_start_date_duration_mode"
                ></i>
            </label>
            <select
                class="tlp-select tlp-select-adjusted end-date-field-id"
                name="end-date-field-id"
                data-test="end-date-field-select-box"
                v-model="user_selected_end_date_field_id"
                v-bind:required="!is_in_start_date_duration_mode"
                v-bind:disabled="is_in_start_date_duration_mode"
            >
                <option value="">{{ $gettext("Choose a field...") }}</option>
                <option
                    v-for="date_field in suitable_end_date_fields"
                    v-bind:value="date_field.id"
                    v-bind:name="date_field.label"
                    v-bind:key="'end-date-field' + date_field.id"
                >
                    {{ date_field.label }}
                </option>
            </select>
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="option-duration">
                <input
                    id="option-duration"
                    type="radio"
                    value="duration"
                    data-test="option-duration"
                    v-bind:checked="is_in_start_date_duration_mode"
                    v-on:click="toggleTimeframeMode(MODE_START_DATE_DURATION)"
                />
                {{ $gettext("Duration") }}
                <i
                    class="fa-solid fa-asterisk"
                    aria-hidden="true"
                    data-test="duration-field-highlight-field-required"
                    v-if="is_in_start_date_duration_mode"
                ></i>
            </label>
            <select
                class="tlp-select tlp-select-adjusted duration-field-id"
                name="duration-field-id"
                data-test="duration-field-select-box"
                v-model="user_selected_duration_field_id"
                v-bind:required="is_in_start_date_duration_mode"
                v-bind:disabled="!is_in_start_date_duration_mode"
            >
                <option value="">{{ $gettext("Choose a field...") }}</option>
                <option
                    v-for="numeric_field in usable_numeric_fields"
                    v-bind:value="numeric_field.id"
                    v-bind:key="numeric_field.id"
                >
                    {{ numeric_field.label }}
                </option>
            </select>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";

import type { TrackerField } from "../type";

const props = defineProps<{
    usable_date_fields: TrackerField[];
    usable_numeric_fields: TrackerField[];
    selected_start_date_field_id: number | "";
    selected_end_date_field_id: number | "";
    selected_duration_field_id: number | "";
}>();

const user_selected_start_date_field_id = ref<number | "">("");
const user_selected_end_date_field_id = ref<number | "">("");
const user_selected_duration_field_id = ref<number | "">("");
const is_in_start_date_duration_mode = ref(false);

const MODE_START_DATE_END_DATE = "MODE_START_DATE_END_DATE";
const MODE_START_DATE_DURATION = "MODE_START_DATE_DURATION";

onMounted((): void => {
    user_selected_start_date_field_id.value = props.selected_start_date_field_id;
    user_selected_end_date_field_id.value = props.selected_end_date_field_id;
    user_selected_duration_field_id.value = props.selected_duration_field_id;
    is_in_start_date_duration_mode.value = Boolean(
        props.selected_start_date_field_id !== "" && props.selected_duration_field_id !== "",
    );
});

function toggleTimeframeMode(mode: string): void {
    is_in_start_date_duration_mode.value = mode === MODE_START_DATE_DURATION;
}

const suitable_start_date_fields = computed((): TrackerField[] => {
    return props.usable_date_fields.filter(
        (date_field) => date_field.id !== user_selected_end_date_field_id.value,
    );
});

const suitable_end_date_fields = computed((): TrackerField[] => {
    return props.usable_date_fields.filter(
        (date_field) => date_field.id !== user_selected_start_date_field_id.value,
    );
});
</script>
