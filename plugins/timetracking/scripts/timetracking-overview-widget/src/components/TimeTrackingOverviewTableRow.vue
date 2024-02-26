<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <tr v-if="display_void_trackers" data-test="timetracking-overview-table-row">
        <td>
            <a v-bind:href="html_url">
                <span>{{ time.label }}</span>
            </a>
        </td>
        <td>
            <a v-bind:href="link_to_project_homepage">{{ time.project.label }}</a>
        </td>
        <td class="tlp-table-cell-numeric">
            {{ overview_store.get_formatted_time(time) }}
        </td>
    </tr>
</template>

<script>
import { inject } from "vue";
import { useOverviewWidgetStore } from "../store/index";

export default {
    name: "TimeTrackingOverviewTableRow",
    props: {
        time: Object,
    },
    setup: () => {
        const overview_store = useOverviewWidgetStore(inject("report_id"))();
        return { overview_store };
    },
    computed: {
        html_url() {
            return "/plugins/tracker/?tracker=" + this.time.id;
        },
        link_to_project_homepage() {
            return "/projects/" + this.time.project.shortname;
        },
        display_void_trackers() {
            return !(
                this.overview_store.are_void_trackers_hidden &&
                this.overview_store.is_tracker_total_sum_equals_zero(this.time.time_per_user)
            );
        },
    },
};
</script>
