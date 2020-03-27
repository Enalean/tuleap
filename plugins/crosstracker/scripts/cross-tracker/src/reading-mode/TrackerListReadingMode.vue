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
    <div class="cross-tracker-reading-mode-trackers-list">
        <div class="cross-tracker-reading-mode-trackers">
            <div
                class="cross-tracker-reading-mode-tracker"
                v-for="{ id, tracker_label, project_label } of trackers"
                v-bind:key="id"
            >
                <span>{{ tracker_label }}</span>
                <span class="cross-tracker-reading-mode-tracker-project-name">
                    <i class="fa fa-archive cross-tracker-report-archive-icon"></i>
                    {{ project_label }}
                </span>
            </div>
        </div>
        <div
            class="cross-tracker-reading-mode-trackers-empty"
            v-if="no_trackers_in_report"
            v-translate
        >
            No trackers selected
        </div>
    </div>
</template>
<script>
export default {
    name: "TrackerListReadingMode",
    props: {
        readingCrossTrackerReport: Object,
    },
    computed: {
        no_trackers_in_report() {
            return this.readingCrossTrackerReport.areTrackersEmpty();
        },
        trackers() {
            const trackers = [...this.readingCrossTrackerReport.getTrackers()];
            return trackers.map(({ tracker, project }) => {
                return {
                    id: tracker.id,
                    tracker_label: tracker.label,
                    project_label: project.label,
                };
            });
        },
    },
};
</script>
