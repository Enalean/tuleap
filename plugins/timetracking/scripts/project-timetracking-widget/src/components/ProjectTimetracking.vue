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
            v-if="project_timetracking_store.has_success_message"
            data-test="report-success"
        >
            {{ project_timetracking_store.success_message }}
        </div>
        <project-timetracking-reading-mode
            v-if="project_timetracking_store.reading_mode"
            data-test="reading-mode"
        />
        <project-timetracking-writing-mode v-else data-test="writing-mode" />
        <project-timetracking-table />
    </div>
</template>

<script setup lang="ts">
import { provide, onMounted, onUnmounted } from "vue";
import { REPORT_ID } from "../injection-symbols";
import ProjectTimetrackingReadingMode from "./reading-mode/ProjectTimetrackingReadingMode.vue";
import ProjectTimetrackingWritingMode from "./writing-mode/ProjectTimetrackingWritingMode.vue";
import ProjectTimetrackingTable from "./ProjectTimetrackingTable.vue";
import { useProjectTimetrackingWidgetStore } from "../store";

const props = defineProps<{
    report_id: number;
    user_id: number;
    are_void_trackers_hidden: boolean;
}>();

const project_timetracking_store = useProjectTimetrackingWidgetStore(props.report_id)();

provide(REPORT_ID, props.report_id);

onMounted(() => {
    project_timetracking_store.setReportId(props.report_id);
    project_timetracking_store.initUserId(props.user_id);
    project_timetracking_store.setDisplayVoidTrackers(props.are_void_trackers_hidden);
    project_timetracking_store.initWidgetWithReport();
    project_timetracking_store.getProjects();
    document.addEventListener("timeUpdated", reloadTimes);
});

onUnmounted(() => {
    document.removeEventListener("timeUpdated", reloadTimes);
});

function reloadTimes(): void {
    project_timetracking_store.reloadProjectTimetrackingTimes();
}
</script>
