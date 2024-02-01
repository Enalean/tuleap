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
    <div class="timetracking-overview-writing-mode">
        <div class="timetracking-overview-selector">
            <time-tracking-overview-writing-dates />
            <time-tracking-overview-writing-trackers />
        </div>
        <time-tracking-overview-tracker-list />
        <div class="timetracking-writing-mode-actions">
            <button
                class="tlp-button-primary tlp-button-outline timetracking-overview-writing-mode-actions-cancel"
                type="button"
                v-on:click="switchToReadingMode()"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                class="tlp-button-primary"
                v-on:click="loadTimes"
                data-test="overview-search-times"
            >
                {{ $gettext("Search") }}
            </button>
        </div>
    </div>
</template>

<script>
import { inject } from "vue";
import TimeTrackingOverviewWritingDates from "./TimeTrackingOverviewWritingDates.vue";
import TimeTrackingOverviewWritingTrackers from "./TimeTrackingOverviewWritingTrackers.vue";
import TimeTrackingOverviewTrackerList from "./TimeTrackingOverviewTrackerList.vue";
import { useOverviewWidgetStore } from "../../store/index.js";

export default {
    name: "TimeTrackingOverviewWritingMode",
    components: {
        TimeTrackingOverviewWritingTrackers,
        TimeTrackingOverviewWritingDates,
        TimeTrackingOverviewTrackerList,
    },
    setup: () => {
        const overview_store = useOverviewWidgetStore(inject("report_id"))();
        return { overview_store };
    },
    methods: {
        loadTimes() {
            this.overview_store.loadTimesWithNewParameters();
        },
        async switchToReadingMode() {
            await this.overview_store.initWidgetWithReport();
            this.overview_store.toggleReadingMode();
        },
    },
};
</script>
