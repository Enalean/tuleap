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
    <div class="cross-tracker-writing-mode">
        <tracker-selection
            v-bind:selected-trackers="selected_trackers"
            v-on:trackerAdded="addTrackerToSelection"
        />
        <tracker-list-writing-mode
            v-bind:trackers="selected_trackers"
            v-on:trackerRemoved="removeTrackerFromSelection"
        />
        <query-editor
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
            v-on:triggerSearch="search"
        />
        <div class="writing-mode-actions">
            <button
                class="tlp-button-primary tlp-button-outline writing-mode-actions-cancel"
                v-on:click="cancel"
                v-translate
            >
                Cancel
            </button>
            <button
                class="tlp-button-primary writing-mode-actions-search"
                v-on:click="search"
                data-test="search-report-button"
            >
                <i class="fa fa-search tlp-button-icon"></i>
                <translate>Search</translate>
            </button>
        </div>
    </div>
</template>
<script>
import QueryEditor from "./QueryEditor.vue";
import TrackerSelection from "./TrackerSelection.vue";
import TrackerListWritingMode from "./TrackerListWritingMode.vue";
import { TooManyTrackersSelectedError } from "./writing-cross-tracker-report.js";

export default {
    name: "WritingMode",
    components: {
        QueryEditor,
        TrackerSelection,
        TrackerListWritingMode,
    },
    props: {
        writingCrossTrackerReport: Object,
    },
    data() {
        return {
            selected_trackers: [],
        };
    },
    mounted() {
        this.updateSelectedTrackers();
    },
    methods: {
        cancel() {
            this.$emit("switchToReadingMode", { saved_state: true });
        },
        search() {
            this.$emit("switchToReadingMode", { saved_state: false });
        },

        addTrackerToSelection({ selected_project, selected_tracker }) {
            try {
                this.writingCrossTrackerReport.addTracker(selected_project, selected_tracker);
                this.updateSelectedTrackers();
            } catch (error) {
                if (error instanceof TooManyTrackersSelectedError) {
                    this.$store.commit(
                        "setErrorMessage",
                        this.$gettext("Tracker selection is limited to 10 trackers")
                    );
                } else {
                    throw error;
                }
            }
        },

        removeTrackerFromSelection({ tracker_id }) {
            this.writingCrossTrackerReport.removeTracker(tracker_id);
            this.updateSelectedTrackers();
            this.$store.commit("resetFeedbacks");
        },

        updateSelectedTrackers() {
            const trackers = [...this.writingCrossTrackerReport.getTrackers()];
            this.selected_trackers = trackers.map(({ tracker, project }) => {
                return {
                    tracker_id: tracker.id,
                    tracker_label: tracker.label,
                    project_label: project.label,
                };
            });
        },
    },
};
</script>
