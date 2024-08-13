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
    <div class="writing-mode">
        <tracker-selection
            v-bind:selected_trackers="selected_trackers"
            v-on:tracker-added="addTrackerToSelection"
        />
        <tracker-list-writing-mode
            v-bind:trackers="selected_trackers"
            v-on:tracker-removed="removeTrackerFromSelection"
        />
        <query-editor
            v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
            v-on:trigger-search="search"
        />
        <div class="actions">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline"
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
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import QueryEditor from "./QueryEditor.vue";
import type { AddTrackerToSelectionCommand } from "./TrackerSelection.vue";
import TrackerSelection from "./TrackerSelection.vue";
import TrackerListWritingMode from "./TrackerListWritingMode.vue";
import type WritingCrossTrackerReport from "./writing-cross-tracker-report";
import type { TrackerToUpdate } from "../type";
import { CLEAR_FEEDBACKS, NOTIFY_FAULT } from "../injection-symbols";

export type SaveEvent = { readonly saved_state: boolean };

const { $gettext } = useGettext();

const notifyFault = strictInject(NOTIFY_FAULT);
const clearFeedbacks = strictInject(CLEAR_FEEDBACKS);

const props = defineProps<{ writing_cross_tracker_report: WritingCrossTrackerReport }>();
const emit = defineEmits<{
    (e: "switch-to-reading-mode", payload: SaveEvent): void;
}>();

const selected_trackers = ref<ReadonlyArray<TrackerToUpdate>>([]);

function cancel(): void {
    emit("switch-to-reading-mode", { saved_state: true });
}

function search(): void {
    emit("switch-to-reading-mode", { saved_state: false });
}

function updateSelectedTrackers(): void {
    const trackers = props.writing_cross_tracker_report.getTrackers();

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
    props.writing_cross_tracker_report
        .addTracker(payload.selected_project, payload.selected_tracker)
        .match(updateSelectedTrackers, notifyFault);
}

function removeTrackerFromSelection(tracker: TrackerToUpdate): void {
    props.writing_cross_tracker_report.removeTracker(tracker.tracker_id);
    updateSelectedTrackers();
    clearFeedbacks();
}

defineExpose({
    selected_trackers,
});
</script>

<style scoped lang="scss">
.writing-mode {
    padding-bottom: var(--tlp-medium-spacing);
    border-bottom: 1px solid var(--tlp-neutral-light-color);
}

.actions {
    display: flex;
    justify-content: center;
    gap: var(--tlp-medium-spacing);
}
</style>
