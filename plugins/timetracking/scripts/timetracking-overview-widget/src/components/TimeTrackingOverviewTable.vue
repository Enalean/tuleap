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
        <div v-if="has_error" class="tlp-alert-danger" data-test="alert-danger">
            {{ error_message }}
        </div>
        <div
            v-if="has_trackers_times && !has_error"
            class="tlp-table-actions"
            data-test="table-action"
        >
            <button
                class="tlp-button-small tlp-button-primary tlp-button-outline tlp-table-actions-element"
                v-on:click="setAreVoidTrackersHidden"
            >
                <i
                    class="fas tlp-button-icon"
                    v-bind:class="[are_void_trackers_hidden ? 'fa-eye' : 'fa-eye-slash']"
                    aria-hidden="true"
                ></i>
                {{ display_button_text }}
            </button>
            <time-tracking-overview-user-list v-if="has_users" data-test="user-list-component" />
        </div>
        <div v-if="is_loading" class="timetracking-loader" data-test="timetracking-loader"></div>
        <table v-if="can_results_be_displayed" class="tlp-table" data-test="overview-table">
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
                    v-for="tracker_time in trackers_times"
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
                        ∑ {{ get_formatted_total_sum }}
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</template>
<script>
import TimeTrackingOverviewTableRow from "./TimeTrackingOverviewTableRow.vue";
import TimeTrackingOverviewUserList from "./TimeTrackingOverviewUserList.vue";
import { mapGetters, mapState } from "vuex";

export default {
    name: "TimeTrackingOverviewTable",
    components: { TimeTrackingOverviewTableRow, TimeTrackingOverviewUserList },
    computed: {
        ...mapGetters([
            "get_formatted_total_sum",
            "has_error",
            "can_results_be_displayed",
            "is_sum_of_times_equals_zero",
        ]),
        ...mapState([
            "trackers_times",
            "error_message",
            "is_loading",
            "are_void_trackers_hidden",
            "users",
        ]),
        time_format_tooltip() {
            return this.$gettext("The time is displayed in hours:minutes");
        },
        has_data_to_display() {
            return (
                this.has_trackers_times &&
                !(this.are_void_trackers_hidden && this.is_sum_of_times_equals_zero)
            );
        },
        has_users() {
            return this.users.length > 0;
        },
        has_trackers_times() {
            return this.trackers_times.length > 0;
        },
        display_button_text() {
            return this.are_void_trackers_hidden
                ? this.$gettext("Show void trackers")
                : this.$gettext("Hide void trackers");
        },
    },
    methods: {
        setAreVoidTrackersHidden() {
            this.$store.dispatch("setPreference");
        },
    },
};
</script>
