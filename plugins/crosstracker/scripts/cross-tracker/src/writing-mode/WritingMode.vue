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
    <div class="cross-tracker-writing-mode">
        <tracker-selection
            v-bind:selected-trackers="selected_trackers"
            v-on:tracker-added="addTrackerToSelection"
        />
        <tracker-list-writing-mode
            v-bind:trackers="selected_trackers"
            v-on:tracker-removed="removeTrackerFromSelection"
        />
        <query-editor
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
            v-on:trigger-search="search"
        />
        <div class="writing-mode-actions">
            <button
                class="tlp-button-primary tlp-button-outline writing-mode-actions-cancel"
                v-on:click="cancel"
                data-test="writing-mode-cancel-button"
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
<script lang="ts">
import QueryEditor from "./QueryEditor.vue";
import TrackerSelection from "./TrackerSelection.vue";
import TrackerListWritingMode from "./TrackerListWritingMode.vue";
import type WritingCrossTrackerReport from "./writing-cross-tracker-report";
import { TooManyTrackersSelectedError } from "./writing-cross-tracker-report";
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { TrackerInfo, TrackerToUpdate, ProjectInfo } from "../type";

interface SelectTrackerAndProject {
    selected_project: ProjectInfo;
    selected_tracker: TrackerInfo;
}

@Component({
    components: { QueryEditor, TrackerListWritingMode, TrackerSelection },
})
export default class WritingMode extends Vue {
    @Prop({ required: true })
    readonly writingCrossTrackerReport!: WritingCrossTrackerReport;

    selected_trackers: TrackerToUpdate[] = [];

    mounted(): void {
        this.updateSelectedTrackers();
    }

    cancel(): void {
        this.$emit("switch-to-reading-mode", { saved_state: true });
    }
    search(): void {
        this.$emit("switch-to-reading-mode", { saved_state: false });
    }

    addTrackerToSelection(payload: SelectTrackerAndProject): void {
        try {
            this.writingCrossTrackerReport.addTracker(
                payload.selected_project,
                payload.selected_tracker,
            );
            this.updateSelectedTrackers();
        } catch (error) {
            if (error instanceof TooManyTrackersSelectedError) {
                this.$store.commit(
                    "setErrorMessage",
                    this.$gettext("Tracker selection is limited to 25 trackers"),
                );
            } else {
                throw error;
            }
        }
    }

    removeTrackerFromSelection(tracker: TrackerToUpdate): void {
        this.writingCrossTrackerReport.removeTracker(tracker.tracker_id);
        this.updateSelectedTrackers();
        this.$store.commit("resetFeedbacks");
    }

    updateSelectedTrackers(): void {
        const trackers = Array.from(this.writingCrossTrackerReport.getTrackers());

        this.selected_trackers = trackers.map(({ tracker, project }): TrackerToUpdate => {
            return {
                tracker_id: tracker.id,
                tracker_label: tracker.label,
                project_label: project.label,
            };
        });
    }
}
</script>
