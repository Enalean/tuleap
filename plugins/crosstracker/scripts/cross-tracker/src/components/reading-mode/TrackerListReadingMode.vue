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
    <div class="list">
        <div class="cross-tracker-reading-mode-trackers">
            <div
                class="cross-tracker-reading-mode-tracker"
                v-for="{ id, tracker_label, project_label } of trackers"
                v-bind:key="id"
            >
                <span>{{ tracker_label }}</span>
                <span class="cross-tracker-reading-mode-tracker-project-name">
                    <i
                        aria-hidden="true"
                        class="fa-solid fa-archive cross-tracker-report-archive-icon"
                    ></i>
                    {{ project_label }}
                </span>
            </div>
        </div>
        <div
            class="cross-tracker-reading-mode-trackers-empty"
            v-if="no_trackers_in_report"
            data-test="empty-state"
        >
            {{ $gettext("No trackers selected") }}
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed } from "vue";
import type { ReadingCrossTrackerReport } from "../../domain/ReadingCrossTrackerReport";

interface TrackerWithProject {
    readonly id: number;
    readonly tracker_label: string;
    readonly project_label: string;
}

const props = defineProps<{ reading_cross_tracker_report: ReadingCrossTrackerReport }>();

const no_trackers_in_report = computed(() => props.reading_cross_tracker_report.areTrackersEmpty());
const trackers = computed((): ReadonlyArray<TrackerWithProject> => {
    return props.reading_cross_tracker_report.getTrackers().map(({ tracker, project }) => {
        return {
            id: tracker.id,
            tracker_label: tracker.label,
            project_label: project.label,
        };
    });
});
</script>

<style scoped lang="scss">
.list {
    display: flex;
    flex-direction: row;
}
</style>
