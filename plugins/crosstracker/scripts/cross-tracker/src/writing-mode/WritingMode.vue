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
                type="button"
                class="tlp-button-primary tlp-button-outline writing-mode-actions-cancel"
                v-on:click="cancel"
                data-test="writing-mode-cancel-button"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary"
                v-on:click="search"
                data-test="search-report-button"
            >
                <i aria-hidden="true" class="fa-solid fa-search tlp-button-icon"></i>
                {{ $gettext("Search") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useMutations } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import QueryEditor from "./QueryEditor.vue";
import type { AddTrackerToSelectionCommand } from "./TrackerSelection.vue";
import TrackerSelection from "./TrackerSelection.vue";
import TrackerListWritingMode from "./TrackerListWritingMode.vue";
import type WritingCrossTrackerReport from "./writing-cross-tracker-report";
import { TooManyTrackersSelectedError } from "./writing-cross-tracker-report";
import type { TrackerToUpdate } from "../type";

export type SaveEvent = { readonly saved_state: boolean };

const { $gettext } = useGettext();

const props = defineProps<{ writingCrossTrackerReport: WritingCrossTrackerReport }>();
const emit = defineEmits<{
    (e: "switch-to-reading-mode", payload: SaveEvent): void;
}>();
const { setErrorMessage, resetFeedbacks } = useMutations(["setErrorMessage", "resetFeedbacks"]);

const selected_trackers = ref<ReadonlyArray<TrackerToUpdate>>([]);

function cancel(): void {
    emit("switch-to-reading-mode", { saved_state: true });
}

function search(): void {
    emit("switch-to-reading-mode", { saved_state: false });
}

function updateSelectedTrackers(): void {
    const trackers = props.writingCrossTrackerReport.getTrackers();

    selected_trackers.value = trackers.map(({ tracker, project }): TrackerToUpdate => {
        return {
            tracker_id: tracker.id,
            tracker_label: tracker.label,
            project_label: project.label,
        };
    });
}

onMounted(() => {
    updateSelectedTrackers();
});

function addTrackerToSelection(payload: AddTrackerToSelectionCommand): void {
    try {
        props.writingCrossTrackerReport.addTracker(
            payload.selected_project,
            payload.selected_tracker,
        );
        updateSelectedTrackers();
    } catch (error) {
        if (error instanceof TooManyTrackersSelectedError) {
            setErrorMessage($gettext("Tracker selection is limited to 25 trackers"));
        } else {
            throw error;
        }
    }
}

function removeTrackerFromSelection(tracker: TrackerToUpdate): void {
    props.writingCrossTrackerReport.removeTracker(tracker.tracker_id);
    updateSelectedTrackers();
    resetFeedbacks();
}

defineExpose({
    selected_trackers,
});
</script>
