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
        <table class="tlp-table">
            <thead>
                <tr>
                    <th v-translate> Tracker </th>
                    <th v-translate> Project </th>
                    <th class="tlp-table-cell-numeric">
                        <translate> Time </translate>
                        <span class="tlp-tooltip tlp-tooltip-left timetracking-time-tooltip"
                              v-bind:data-tlp-tooltip="time_format_tooltip"
                              v-bind:aria-label="time_format_tooltip"
                        >
                            <i class="fa fa-question-circle"></i>
                        </span>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" class="tlp-table-cell-empty" v-translate>
                        No time have been found for this period and these trackers
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: "TimeTrackingOverviewTable",
    computed: {
        time_format_tooltip() {
            return this.$gettext("The time is displayed in hours:minutes");
        }
    }
};
</script>
