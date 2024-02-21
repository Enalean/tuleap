<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <form class="timetracking-writing-mode">
        <div class="timetracking-writing-mode-selected-dates">
            <div class="tlp-form-element timetracking-writing-mode-selected-date">
                <label for="timetracking-start-date" class="tlp-label">
                    {{ $gettext("From") }}
                    <i class="fa fa-asterisk"></i>
                </label>
                <div class="tlp-form-element tlp-form-element-prepend">
                    <span class="tlp-prepend"><i class="fas fa-calendar-alt"></i></span>
                    <input
                        type="text"
                        class="tlp-input tlp-input-date"
                        id="timetracking-start-date"
                        ref="start_date_picker"
                        v-model="personal_store.start_date"
                        size="11"
                        data-test="timetracking-start-date"
                    />
                </div>
            </div>

            <div class="tlp-form-element timetracking-writing-mode-selected-date">
                <label for="timetracking-end-date" class="tlp-label">
                    {{ $gettext("To") }}
                    <i class="fa fa-asterisk"></i>
                </label>
                <div class="tlp-form-element tlp-form-element-prepend">
                    <span class="tlp-prepend"><i class="fas fa-calendar-alt"></i></span>
                    <input
                        type="text"
                        class="tlp-input tlp-input-date"
                        id="timetracking-end-date"
                        ref="end_date_picker"
                        v-model="personal_store.end_date"
                        size="11"
                        data-test="timetracking-end-date"
                    />
                </div>
            </div>
        </div>
        <div class="timetracking-writing-mode-actions">
            <button
                class="tlp-button-primary tlp-button-outline"
                type="button"
                v-on:click="personal_store.toggleReadingMode"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                class="tlp-button-primary timetracking-writing-search"
                type="button"
                data-test="timetracking-search-for-dates"
                v-on:click="changeDates"
            >
                {{ $gettext("Search") }}
            </button>
        </div>
    </form>
</template>
<script setup lang="ts">
import { datePicker } from "tlp";
import { usePersonalTimetrackingWidgetStore } from "../store/root";
import { onMounted, ref } from "vue";
import type { Ref } from "vue";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const personal_store = usePersonalTimetrackingWidgetStore();

let start_date_picker: Ref<HTMLInputElement | undefined> = ref();
let end_date_picker: Ref<HTMLInputElement | undefined> = ref();

const isHTMLInputElement = (element: HTMLElement | undefined): element is HTMLInputElement => {
    return element instanceof HTMLInputElement;
};

onMounted((): void => {
    [start_date_picker.value, end_date_picker.value].forEach((element) => {
        if (!isHTMLInputElement(element)) {
            return;
        }

        datePicker(element);
    });
});

const changeDates = (): void => {
    const start_date_input = start_date_picker.value;
    const end_date_input = end_date_picker.value;

    if (!isHTMLInputElement(start_date_input) || !isHTMLInputElement(end_date_input)) {
        return;
    }

    personal_store.setDatesAndReload(String(start_date_input.value), String(end_date_input.value));
};
</script>

<style scoped lang="scss">
.timetracking-writing-mode-selected-dates {
    display: flex;
    min-width: max-content;
    margin: var(--tlp-medium-spacing) var(--tlp-medium-spacing) 0;
}

.timetracking-writing-mode-selected-date:last-of-type {
    margin: 0 0 0 var(--tlp-medium-spacing);
}

.timetracking-writing-mode-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: var(--tlp-medium-spacing);
}

.timetracking-writing-search {
    margin: 0 0 0 var(--tlp-medium-spacing);
}
</style>
