<!--
  - Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
    <div class="timetracking-management-query-editor">
        <div class="tlp-form-element">
            <label for="timetracking-management-query-editor-start-date" class="tlp-label">
                {{ $gettext("From") }}
                <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
            </label>
            <div class="tlp-form-element tlp-form-element-prepend">
                <span class="tlp-prepend">
                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                </span>
                <input
                    type="text"
                    class="tlp-input tlp-input-date"
                    id="timetracking-management-query-editor-start-date"
                    ref="start_date_input"
                    size="11"
                    data-test="start-date-input"
                />
            </div>
        </div>
        <div class="tlp-form-element">
            <label for="timetracking-management-query-editor-end-date" class="tlp-label">
                {{ $gettext("To") }}
                <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
            </label>
            <div class="tlp-form-element tlp-form-element-prepend">
                <span class="tlp-prepend">
                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                </span>
                <input
                    type="text"
                    class="tlp-input tlp-input-date"
                    id="timetracking-management-query-editor-end-date"
                    ref="end_date_input"
                    size="11"
                    data-test="end-date-input"
                />
            </div>
        </div>
    </div>
    <div class="timetracking-management-query-editor-actions">
        <button
            class="tlp-button-primary tlp-button-outline"
            type="button"
            data-test="cancel-button"
            v-on:click="$emit('closeEditMode')"
        >
            {{ $gettext("Cancel") }}
        </button>
        <button
            class="tlp-button-primary"
            data-test="search-button"
            type="button"
            v-on:click="setDatesAndCloseEditMode"
        >
            {{ $gettext("Search") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { datePicker, type DatePickerInstance } from "tlp";
import { useGettext } from "vue3-gettext";
import type { Ref } from "vue";
import { onBeforeUnmount, onMounted, ref } from "vue";

const { $gettext } = useGettext();

const props = defineProps<{
    start_date: string;
    end_date: string;
}>();

const start_date_input: Ref<HTMLInputElement | undefined> = ref();
const end_date_input: Ref<HTMLInputElement | undefined> = ref();

let start_date_picker: DatePickerInstance;
let end_date_picker: DatePickerInstance;

const emit = defineEmits<{
    (e: "setDates", start_date: string, end_date: string): void;
    (e: "closeEditMode"): void;
}>();

const isHTMLInputElement = (element: HTMLElement | undefined): element is HTMLInputElement => {
    return element instanceof HTMLInputElement;
};

onMounted((): void => {
    if (!isHTMLInputElement(start_date_input.value) || !isHTMLInputElement(end_date_input.value)) {
        return;
    }
    start_date_picker = datePicker(start_date_input.value);
    start_date_picker.setDate(props.start_date);

    end_date_picker = datePicker(end_date_input.value);
    end_date_picker.setDate(props.end_date);
});

onBeforeUnmount((): void => {
    start_date_picker.destroy();
    end_date_picker.destroy();
});

const setDatesAndCloseEditMode = (): void => {
    emit("setDates", String(start_date_input.value?.value), String(end_date_input.value?.value));
    emit("closeEditMode");
};
</script>

<style scoped lang="scss">
.timetracking-management-query-editor {
    display: flex;
    gap: var(--tlp-medium-spacing);
}

.timetracking-management-query-editor-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--tlp-medium-spacing);
    gap: var(--tlp-medium-spacing);
}
</style>
