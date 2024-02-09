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
    <div class="cross-timetracking-widget">
        <div
            class="tlp-alert-info cross-tracker-report-success"
            v-if="overview_store.has_success_message"
            data-test="report-success"
        >
            {{ overview_store.success_message }}
        </div>
        <time-tracking-overview-reading-mode
            v-if="overview_store.reading_mode"
            data-test="reading-mode"
        />
        <time-tracking-overview-writing-mode v-else data-test="writing-mode" />
        <time-tracking-overview-table />
    </div>
</template>

<script>
import { provide } from "vue";
import TimeTrackingOverviewReadingMode from "./reading-mode/TimeTrackingOverviewReadingMode.vue";
import TimeTrackingOverviewWritingMode from "./writing-mode/TimeTrackingOverviewWritingMode.vue";
import TimeTrackingOverviewTable from "./TimeTrackingOverviewTable.vue";
import { useOverviewWidgetStore } from "../store/index.js";

export default {
    name: "TimeTrackingOverview",
    components: {
        TimeTrackingOverviewTable,
        TimeTrackingOverviewReadingMode,
        TimeTrackingOverviewWritingMode,
    },
    props: {
        reportId: Number,
        userId: Number,
        areVoidTrackersHidden: Boolean,
    },
    setup: ({ reportId }) => {
        provide("report_id", reportId);
        const overview_store = useOverviewWidgetStore(reportId)();

        return { overview_store };
    },
    mounted() {
        this.overview_store.setReportId(this.reportId);
        this.overview_store.initUserId(this.userId);
        this.overview_store.setDisplayVoidTrackers(this.areVoidTrackersHidden);
        this.overview_store.initWidgetWithReport();
        this.overview_store.getProjects();
        document.addEventListener("timeUpdated", this.reloadTimes);
    },
    destroyed() {
        document.removeEventListener("timeUpdated", this.reloadTimes);
    },
    methods: {
        reloadTimes() {
            this.overview_store.reloadTimetrackingOverviewTimes();
        },
    },
};
</script>
