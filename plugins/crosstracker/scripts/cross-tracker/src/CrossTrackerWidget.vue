<!--
  - Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
    <div>
        <error-message />
        <error-inactive-project-message />
        <div
            class="tlp-alert-info cross-tracker-report-success"
            v-if="has_success_message"
            data-test="cross-tracker-report-success"
        >
            {{ success_message }}
        </div>
        <div class="cross-tracker-loader" v-if="is_loading"></div>
        <reading-mode
            v-if="is_reading_mode_shown"
            v-bind:backend-cross-tracker-report="backendCrossTrackerReport"
            v-bind:reading-cross-tracker-report="readingCrossTrackerReport"
            v-on:switch-to-writing-mode="handleSwitchWriting"
            v-on:saved="reportSaved"
        />
        <writing-mode
            v-if="!reading_mode"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
            v-on:switch-to-reading-mode="handleSwitchReading"
        />
        <artifact-table
            v-if="!is_loading"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
        />
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useGetters, useMutations, useState } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import ArtifactTable from "./components/ArtifactTable.vue";
import ReadingMode from "./reading-mode/ReadingMode.vue";
import type { SaveEvent } from "./writing-mode/WritingMode.vue";
import WritingMode from "./writing-mode/WritingMode.vue";
import ErrorMessage from "./components/ErrorMessage.vue";
import ErrorInactiveProjectMessage from "./components/ErrorInactiveProjectMessage.vue";
import { getReport } from "./api/rest-querier";
import type WritingCrossTrackerReport from "./writing-mode/writing-cross-tracker-report";
import type BackendCrossTrackerReport from "./backend-cross-tracker-report";
import type ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report";
import type { State, Report } from "./type";

const gettext_provider = useGettext();

const { reading_mode, report_id, success_message, is_user_admin } = useState<
    Pick<State, "reading_mode" | "report_id" | "success_message" | "is_user_admin">
>(["reading_mode", "report_id", "success_message", "is_user_admin"]);

const { has_success_message } = useGetters(["has_success_message"]);
const {
    setInvalidTrackers,
    setErrorMessage,
    switchToWritingMode,
    switchToReadingMode,
    resetInvalidTrackerList,
    switchReportToSaved,
} = useMutations([
    "setInvalidTrackers",
    "setErrorMessage",
    "switchToWritingMode",
    "switchToReadingMode",
    "resetInvalidTrackerList",
    "switchReportToSaved",
]);

const props = defineProps<{
    backendCrossTrackerReport: BackendCrossTrackerReport;
    readingCrossTrackerReport: ReadingCrossTrackerReport;
    writingCrossTrackerReport: WritingCrossTrackerReport;
}>();

const is_loading = ref(true);

const is_reading_mode_shown = computed(() => reading_mode.value === true && !is_loading.value);

function initReports(): void {
    props.readingCrossTrackerReport.duplicateFromReport(props.backendCrossTrackerReport);
    props.writingCrossTrackerReport.duplicateFromReport(props.readingCrossTrackerReport);
}

function loadBackendReport(): void {
    is_loading.value = true;
    getReport(report_id.value)
        .match(
            (report: Report) => {
                props.backendCrossTrackerReport.init(report.trackers, report.expert_query);
                initReports();
                if (report.invalid_trackers.length > 0) {
                    setInvalidTrackers(report.invalid_trackers);
                }
            },
            (fault) => {
                setErrorMessage(String(fault));
            },
        )
        .then(() => {
            is_loading.value = false;
        });
}

onMounted(() => {
    loadBackendReport();
});

function handleSwitchWriting(): void {
    if (!is_user_admin.value) {
        return;
    }

    props.writingCrossTrackerReport.duplicateFromReport(props.readingCrossTrackerReport);
    switchToWritingMode();
}

function handleSwitchReading(event: SaveEvent): void {
    if (event.saved_state) {
        props.writingCrossTrackerReport.duplicateFromReport(props.readingCrossTrackerReport);
    } else {
        props.readingCrossTrackerReport.duplicateFromWritingReport(props.writingCrossTrackerReport);
    }
    switchToReadingMode(event.saved_state);
}

function reportSaved(): void {
    initReports();
    resetInvalidTrackerList();
    switchReportToSaved(gettext_provider.$gettext("Report has been successfully saved"));
}
</script>
