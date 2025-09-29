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
    <div class="project-timetracking-writing-mode">
        <div class="project-timetracking-selector">
            <project-timetracking-writing-dates />
            <project-timetracking-writing-trackers />
        </div>
        <project-timetracking-tracker-list />
        <div class="timetracking-writing-mode-actions">
            <button
                class="tlp-button-primary tlp-button-outline project-timetracking-writing-mode-actions-cancel"
                type="button"
                v-on:click="switchToReadingMode()"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                class="tlp-button-primary"
                v-on:click="loadTimes"
                data-test="project-timetracking-search-times"
            >
                {{ $gettext("Search") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { REPORT_ID } from "../../injection-symbols";
import { useProjectTimetrackingWidgetStore } from "../../store";
import ProjectTimetrackingWritingDates from "./ProjectTimetrackingWritingDates.vue";
import ProjectTimetrackingWritingTrackers from "./ProjectTimetrackingWritingTrackers.vue";
import ProjectTimetrackingTrackerList from "./ProjectTimetrackingTrackerList.vue";

const { $gettext } = useGettext();

const project_timetracking_store = useProjectTimetrackingWidgetStore(strictInject(REPORT_ID))();

function loadTimes(): void {
    project_timetracking_store.loadTimesWithNewParameters();
}

function switchToReadingMode(): void {
    project_timetracking_store.initWidgetWithReport();
    project_timetracking_store.toggleReadingMode();
}
</script>
