<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
        <div v-if="has_error" class="tlp-alert-danger">
            {{ error_message }}
        </div>
        <div v-if="is_loading" class="timetracking-loader"></div>
        <table v-if="can_results_be_displayed" class="tlp-table">
            <thead>
                <tr>
                    <th v-translate>
                        Tracker
                    </th>
                    <th v-translate>
                        Project
                    </th>
                    <th class="tlp-table-cell-numeric">
                        <translate> Time</translate>
                        <span class="tlp-tooltip tlp-tooltip-left timetracking-time-tooltip"
                              v-bind:data-tlp-tooltip="time_format_tooltip"
                              v-bind:aria-label="time_format_tooltip"
                        >
                            <i class="fa fa-question-circle"></i>
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="! has_data_to_display">
                    <td colspan="4" class="tlp-table-cell-empty" v-translate>
                        No time have been found for this period and these trackers
                    </td>
                </tr>
                <time-tracking-overview-table-row v-else
                                                  v-for="tracker_time in trackers_times"
                                                  v-bind:key="tracker_time.id"
                                                  v-bind:time="tracker_time"
                />
            </tbody>
            <tfoot v-if="has_data_to_display">
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
import { mapGetters, mapState } from "vuex";

export default {
    name: "TimeTrackingOverviewTable",
    components: { TimeTrackingOverviewTableRow },
    computed: {
        ...mapGetters(["get_formatted_total_sum", "has_error", "can_results_be_displayed"]),
        ...mapState(["trackers_times", "error_message", "is_loading"]),
        time_format_tooltip() {
            return this.$gettext("The time is displayed in hours:minutes");
        },
        has_data_to_display() {
            return this.trackers_times.length > 0;
        }
    }
};
</script>
