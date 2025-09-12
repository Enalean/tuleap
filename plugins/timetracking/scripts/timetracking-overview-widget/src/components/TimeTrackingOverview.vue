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

<script setup lang="ts">
import { provide, onMounted, onUnmounted } from "vue";
import { REPORT_ID } from "../injection-symbols";
import TimeTrackingOverviewReadingMode from "./reading-mode/TimeTrackingOverviewReadingMode.vue";
import TimeTrackingOverviewWritingMode from "./writing-mode/TimeTrackingOverviewWritingMode.vue";
import TimeTrackingOverviewTable from "./TimeTrackingOverviewTable.vue";
import { useOverviewWidgetStore } from "../store";

const props = defineProps<{
    report_id: number;
    user_id: number;
    are_void_trackers_hidden: boolean;
}>();

const overview_store = useOverviewWidgetStore(props.report_id)();

provide(REPORT_ID, props.report_id);

onMounted(() => {
    overview_store.setReportId(props.report_id);
    overview_store.initUserId(props.user_id);
    overview_store.setDisplayVoidTrackers(props.are_void_trackers_hidden);
    overview_store.initWidgetWithReport();
    overview_store.getProjects();
    document.addEventListener("timeUpdated", reloadTimes);
});

onUnmounted(() => {
    document.removeEventListener("timeUpdated", reloadTimes);
});

function reloadTimes(): void {
    overview_store.reloadTimetrackingOverviewTimes();
}
</script>
