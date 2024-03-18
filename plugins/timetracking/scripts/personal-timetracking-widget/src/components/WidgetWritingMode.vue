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

            <div class="tlp-form-element timetracking-writing-mode-selected-date">
                <label for="timetracking-predefined-date" class="tlp-label">
                    {{ $gettext("Predefined periods") }}
                </label>
                <div class="tlp-form-element tlp-form-element-prepend">
                    <span class="tlp-prepend"><i class="fas fa-calendar-alt"></i></span>
                    <select
                        class="tlp-select tlp-input"
                        id="period"
                        ref="select"
                        v-model="selected_option"
                        v-on:change="applyDatesPreset"
                        data-test="timetracking-predefined-periods"
                    >
                        <option value="">{{ $gettext("Please choose...") }}</option>
                        <option v-bind:value="TODAY">{{ $gettext("Today") }}</option>
                        <option v-bind:value="YESTERDAY">{{ $gettext("Yesterday") }}</option>
                        <option v-bind:value="LAST_7_DAYS">{{ $gettext("Last 7 days") }}</option>
                        <option v-bind:value="CURRENT_WEEK">{{ $gettext("Current week") }}</option>
                        <option v-bind:value="LAST_WEEK">{{ $gettext("Last week") }}</option>
                        <option v-bind:value="LAST_MONTH">{{ $gettext("Last month") }}</option>
                    </select>
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
import type { DatePickerInstance } from "tlp";
import { usePersonalTimetrackingWidgetStore } from "../store/root";
import { onMounted, ref } from "vue";
import type { Ref } from "vue";
import { useGettext } from "vue3-gettext";
import type { Period, PredefinedTimePeriod } from "../helper/predefined-time-periods";
import {
    TODAY,
    YESTERDAY,
    LAST_7_DAYS,
    LAST_WEEK,
    CURRENT_WEEK,
    LAST_MONTH,
    getTodayPeriod,
    getYesterdayPeriod,
    getCurrentWeekPeriod,
    getLastWeekPeriod,
    getLastMonthPeriod,
    getLastSevenDaysPeriod,
} from "../helper/predefined-time-periods";
import { Option } from "@tuleap/option";

const { $gettext } = useGettext();
const personal_store = usePersonalTimetrackingWidgetStore();

let start_date_input: Ref<HTMLInputElement | undefined> = ref();
let end_date_input: Ref<HTMLInputElement | undefined> = ref();
let selected_option: Ref<PredefinedTimePeriod | ""> = ref(
    personal_store.selected_time_period.unwrapOr(""),
);

let start_date_picker: DatePickerInstance;
let end_date_picker: DatePickerInstance;

const isHTMLInputElement = (element: HTMLElement | undefined): element is HTMLInputElement => {
    return element instanceof HTMLInputElement;
};

onMounted((): void => {
    if (!isHTMLInputElement(start_date_input.value) || !isHTMLInputElement(end_date_input.value)) {
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
};

const applyDatesPreset = (): void => {
    const setDatePickersValues = (period: Period): void => {
        start_date_picker.setDate(period.start);
        end_date_picker.setDate(period.end);
    };

    switch (selected_option.value) {
        case TODAY:
            personal_store.selected_time_period = Option.fromValue(TODAY);
            return setDatePickersValues(getTodayPeriod());
        case YESTERDAY:
            personal_store.selected_time_period = Option.fromValue(YESTERDAY);
            return setDatePickersValues(getYesterdayPeriod());
        case LAST_7_DAYS:
            personal_store.selected_time_period = Option.fromValue(LAST_7_DAYS);
            return setDatePickersValues(getLastSevenDaysPeriod());
        case CURRENT_WEEK:
            personal_store.selected_time_period = Option.fromValue(CURRENT_WEEK);
            return setDatePickersValues(getCurrentWeekPeriod());
        case LAST_WEEK:
            personal_store.selected_time_period = Option.fromValue(LAST_WEEK);
            return setDatePickersValues(getLastWeekPeriod());
        case LAST_MONTH:
            personal_store.selected_time_period = Option.fromValue(LAST_MONTH);
            return setDatePickersValues(getLastMonthPeriod());
        default:
            return resetSelectedOption();
    }
};

const changeDates = (): void => {
    if (!isHTMLInputElement(start_date_input.value) || !isHTMLInputElement(end_date_input.value)) {
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
