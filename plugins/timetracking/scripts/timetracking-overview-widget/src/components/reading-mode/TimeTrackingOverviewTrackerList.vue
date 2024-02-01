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
    <div class="timetracking-overview-reading-mode-trackers-list">
        <div class="timetracking-overview-reading-mode-trackers">
            <div
                class="timetracking-overview-reading-mode-tracker"
                v-for="tracker of overview_store.selected_trackers"
                v-bind:key="tracker.id"
            >
                <span>{{ tracker.label }}</span>
                <span class="timetracking-overview-reading-mode-tracker-project-name">
                    <i class="fa fa-archive timetracking-archive"></i>
                    {{ tracker.project.label }}
                </span>
            </div>
        </div>
        <div
            class="timetracking-overview-reading-mode-trackers-empty"
            v-if="has_no_trackers_in_report"
            data-test="timetracking-overview-reading-mode-trackers-empty"
        >
            {{ $gettext("No trackers selected") }}
        </div>
    </div>
</template>
<script>
import { inject } from "vue";
import { useOverviewWidgetStore } from "../../store/index.js";

export default {
    name: "TimeTrackingOverviewTrackerList",
    setup: () => {
        const overview_store = useOverviewWidgetStore(inject("report_id"))();
        return { overview_store };
    },
    computed: {
        has_no_trackers_in_report() {
            return this.overview_store.selected_trackers.length === 0;
        },
    },
};
</script>
