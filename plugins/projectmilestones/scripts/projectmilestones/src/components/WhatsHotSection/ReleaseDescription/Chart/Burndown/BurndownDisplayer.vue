<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
        <chart-error
            v-if="has_error_duration || has_error_start_date || is_under_calculation"
            v-bind:has_error_duration="has_error_duration"
            v-bind:message_error_duration="message_error_duration"
            v-bind:has_error_start_date="has_error_start_date"
            v-bind:message_error_start_date="message_error_start_date"
            v-bind:is_under_calculation="is_under_calculation"
            v-bind:message_error_under_calculation="burndown_under_calculation_label"
        />
        <burndown-chart
            v-else
            v-bind:release_data="release_data"
            v-bind:burndown_data="burndown_data"
        />
    </div>
</template>

<script setup lang="ts">
import type { BurndownData, MilestoneData } from "../../../../../type";
import { computed } from "vue";
import ChartError from "../ChartError.vue";
import { useStore } from "../../../../../stores/root";
import { useGettext } from "vue3-gettext";
import BurndownChart from "./BurndownChart.vue";

const props = defineProps<{ release_data: MilestoneData; burndown_data: BurndownData | null }>();

const root_store = useStore();
const { $gettext, interpolate } = useGettext();

const burndown_under_calculation_label = $gettext(
    "Burndown is under calculation. It will be available in a few minutes.",
);

const message_error_duration = computed((): string => {
    return interpolate($gettext("'%{field_name}' field is empty or invalid."), {
        field_name: root_store.label_timeframe,
    });
});
const message_error_start_date = computed((): string => {
    return interpolate($gettext("'%{field_name}' field is empty or invalid."), {
        field_name: root_store.label_start_date,
    });
});
const has_error_duration = computed((): boolean => {
    if (!root_store.is_timeframe_duration) {
        return !props.release_data.end_date;
    }

    if (!props.burndown_data) {
        return true;
    }

    return props.burndown_data.duration === null || props.burndown_data.duration === 0;
});
const has_error_start_date = computed((): boolean => {
    return !props.release_data.start_date;
});
const is_under_calculation = computed((): boolean => {
    if (!props.burndown_data) {
        return false;
    }

    return props.burndown_data.is_under_calculation;
});
</script>
