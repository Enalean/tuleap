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
    <div class="timetracking-overview-widget-table">
        <div v-if="overview_store.has_error" class="tlp-alert-danger" data-test="alert-danger">
            {{ overview_store.error_message }}
        </div>
        <div
            v-if="has_trackers_times && !overview_store.has_error"
            class="tlp-table-actions"
            data-test="table-action"
        >
            <button
                class="tlp-button-small tlp-button-primary tlp-button-outline tlp-table-actions-element"
                v-on:click="setAreVoidTrackersHidden"
            >
                <i
                    class="fas tlp-button-icon"
                    v-bind:class="[
                        overview_store.are_void_trackers_hidden ? 'fa-eye' : 'fa-eye-slash',
                    ]"
                    aria-hidden="true"
                ></i>
                {{ display_button_text }}
            </button>
            <time-tracking-overview-user-list v-if="has_users" data-test="user-list-component" />
        </div>
        <div
            v-if="overview_store.is_loading"
            class="timetracking-loader"
            data-test="timetracking-loader"
        ></div>
        <table
            v-if="overview_store.can_results_be_displayed"
            class="tlp-table"
            data-test="overview-table"
        >
            <thead>
                <tr>
                    <th>{{ $gettext("Tracker") }}</th>
                    <th>{{ $gettext("Project") }}</th>
                    <th class="tlp-table-cell-numeric">
                        {{ $gettext("Time") }}
                        <span
                            class="tlp-tooltip tlp-tooltip-left timetracking-time-tooltip"
                            v-bind:data-tlp-tooltip="time_format_tooltip"
                            v-bind:aria-label="time_format_tooltip"
                        >
                            <i class="fa fa-question-circle"></i>
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="!has_data_to_display">
                    <td colspan="4" class="tlp-table-cell-empty" data-test="empty-cell">
                        {{ $gettext("No time have been found for this period and these trackers") }}
                    </td>
                </tr>
                <time-tracking-overview-table-row
                    v-else
                    v-for="tracker_time in overview_store.trackers_times"
                    v-bind:key="tracker_time.id"
                    v-bind:time="tracker_time"
                    data-test="table-row"
                />
            </tbody>
            <tfoot v-if="has_data_to_display" data-test="tfoot">
                <tr>
                    <th></th>
                    <th></th>
                    <th class="tlp-table-cell-numeric timetracking-total-sum">
                        ∑ {{ overview_store.get_formatted_total_sum }}
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { REPORT_ID } from "../injection-symbols";
import { useOverviewWidgetStore } from "../store";
import TimeTrackingOverviewTableRow from "./TimeTrackingOverviewTableRow.vue";
import TimeTrackingOverviewUserList from "./TimeTrackingOverviewUserList.vue";

const { $gettext } = useGettext();

const overview_store = useOverviewWidgetStore(strictInject(REPORT_ID))();

const time_format_tooltip = $gettext("The time is displayed in hours:minutes");

const has_trackers_times = computed(() => overview_store.trackers_times.length > 0);

const has_data_to_display = computed(() => {
    return (
        has_trackers_times.value &&
        !(overview_store.are_void_trackers_hidden && overview_store.is_sum_of_times_equals_zero)
    );
});
const has_users = computed(() => overview_store.users.length > 0);

const display_button_text = computed(() => {
    return overview_store.are_void_trackers_hidden
        ? $gettext("Show void trackers")
        : $gettext("Hide void trackers");
});

function setAreVoidTrackersHidden(): void {
    overview_store.setPreference();
}
</script>
