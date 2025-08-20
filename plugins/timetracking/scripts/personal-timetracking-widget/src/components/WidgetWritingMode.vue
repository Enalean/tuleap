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
                        ref="start_date_input"
                        size="11"
                        v-on:change="resetSelectedOption"
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
                        ref="end_date_input"
                        size="11"
                        v-on:change="resetSelectedOption"
                        data-test="timetracking-end-date"
                    />
                </div>
            </div>

            <tuleap-predefined-time-period-select
                ref="predefined_time_period_select"
                v-bind:onselection="setDatePickersValues"
                v-bind:selected_time_period="selected_option"
            />
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
import type { DatePickerInstance } from "@tuleap/tlp-date-picker";
import { datePicker } from "@tuleap/tlp-date-picker";
import { usePersonalTimetrackingWidgetStore } from "../store/root";
import { onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import type {
    PeriodOption,
    PredefinedTimePeriod,
    PredefinedTimePeriodSelect,
} from "@tuleap/plugin-timetracking-predefined-time-periods";
import { Option } from "@tuleap/option";
import { formatDatetimeToYearMonthDay } from "@tuleap/plugin-timetracking-time-formatters";

const { $gettext } = useGettext();
const personal_store = usePersonalTimetrackingWidgetStore();

let start_date_input = ref<HTMLInputElement>();
let end_date_input = ref<HTMLInputElement>();
let selected_option = ref<PredefinedTimePeriod | "">(
    personal_store.selected_time_period.unwrapOr(""),
);

let start_date_picker: DatePickerInstance;
let end_date_picker: DatePickerInstance;

const predefined_time_period_select = ref<PredefinedTimePeriodSelect>();

onMounted((): void => {
    if (
        !(start_date_input.value instanceof HTMLInputElement) ||
        !(end_date_input.value instanceof HTMLInputElement)
    ) {
        return;
    }

    start_date_picker = datePicker(start_date_input.value);
    start_date_picker.setDate(personal_store.start_date);

    end_date_picker = datePicker(end_date_input.value);
    end_date_picker.setDate(personal_store.end_date);
});

const resetSelectedOption = (): void => {
    selected_option.value = "";
    personal_store.selected_time_period = Option.nothing();
    predefined_time_period_select.value?.resetSelection();
};

const setDatePickersValues = (
    selected_time_period: PredefinedTimePeriod | "",
    period: PeriodOption,
): void => {
    selected_option.value = selected_time_period;
    period.apply((period) => {
        start_date_picker.setDate(formatDatetimeToYearMonthDay(period.start));
        end_date_picker.setDate(formatDatetimeToYearMonthDay(period.end));
    });

    personal_store.selected_time_period =
        selected_option.value === "" ? Option.nothing() : Option.fromValue(selected_option.value);
};

const changeDates = (): void => {
    if (
        !(start_date_input.value instanceof HTMLInputElement) ||
        !(end_date_input.value instanceof HTMLInputElement)
    ) {
        return;
    }

    personal_store.setDatesAndReload(
        String(start_date_input.value.value),
        String(end_date_input.value.value),
    );
};
</script>

<style scoped lang="scss">
.timetracking-writing-mode-selected-dates {
    display: flex;
    gap: var(--tlp-medium-spacing);
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
