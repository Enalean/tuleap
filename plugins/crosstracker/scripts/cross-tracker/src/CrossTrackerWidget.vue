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
    <section
        class="tlp-pane-section"
        v-bind:class="{ 'reading-mode-shown': is_reading_mode_shown }"
    >
        <div
            class="action-buttons"
            v-if="is_multiple_query_supported && report_state !== 'edit-query'"
        >
            <action-buttons
                v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
                v-bind:reading_cross_tracker_report="reading_cross_tracker_report"
                v-bind:queries="queries"
                v-bind:selected_query="selected_query"
            />
        </div>
        <error-message
            v-bind:fault="current_fault"
            v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
        />
        <div
            class="tlp-alert-success cross-tracker-report-success"
            v-if="current_success.isValue()"
            data-test="cross-tracker-report-success"
        >
            {{ current_success.unwrapOr("") }}
        </div>
        <div class="cross-tracker-loader" v-if="is_loading"></div>
        <reading-mode
            v-if="is_reading_mode_shown"
            v-bind:backend_cross_tracker_report="backend_cross_tracker_report"
            v-bind:reading_cross_tracker_report="reading_cross_tracker_report"
            v-bind:has_error="has_error"
            v-on:switch-to-writing-mode="handleSwitchWriting"
            v-on:saved="reportSaved"
            v-on:discard-unsaved-report="unsavedReportDiscarded"
        />
        <writing-mode
            v-if="report_state === 'edit-query'"
            v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
            v-on:preview-result="handlePreviewResult"
            v-on:cancel-query-edition="handleCancelQueryEdition"
        />
    </section>
    <section class="tlp-pane-section" v-if="!is_loading">
        <selectable-table
            v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
            v-bind:selected_query="selected_query"
        />
    </section>
</template>
<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, provide, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import ReadingMode from "./components/reading-mode/ReadingMode.vue";
import WritingMode from "./components/writing-mode/WritingMode.vue";
import ErrorMessage from "./components/ErrorMessage.vue";
import { getReports } from "./api/rest-querier";
import type { WritingCrossTrackerReport } from "./domain/WritingCrossTrackerReport";
import type { BackendCrossTrackerReport } from "./domain/BackendCrossTrackerReport";
import type { ReadingCrossTrackerReport } from "./domain/ReadingCrossTrackerReport";
import type { Report } from "./type";
import SelectableTable from "./components/selectable-table/SelectableTable.vue";
import type { ReportState } from "./domain/ReportState";
import {
    CLEAR_FEEDBACKS,
    EMITTER,
    IS_EXPORT_ALLOWED,
    IS_MULTIPLE_QUERY_SUPPORTED,
    IS_USER_ADMIN,
    NOTIFY_FAULT,
    REPORT_ID,
    REPORT_STATE,
} from "./injection-symbols";
import { useFeedbacks } from "./composables/useFeedbacks";
import { ReportRetrievalFault } from "./domain/ReportRetrievalFault";
import ActionButtons from "./components/actions/ActionButtons.vue";
import { SWITCH_QUERY_EVENT } from "./helpers/emitter-provider";

const report_id = strictInject(REPORT_ID);
const is_user_admin = strictInject(IS_USER_ADMIN);
const emitter = strictInject(EMITTER);
const is_multiple_query_supported = strictInject(IS_MULTIPLE_QUERY_SUPPORTED);

const gettext_provider = useGettext();

const props = defineProps<{
    backend_cross_tracker_report: BackendCrossTrackerReport;
    reading_cross_tracker_report: ReadingCrossTrackerReport;
    writing_cross_tracker_report: WritingCrossTrackerReport;
}>();

const report_state = ref<ReportState>("report-saved");
provide(REPORT_STATE, report_state);
const is_loading = ref(true);
const queries = ref<ReadonlyArray<Report>>([]);
const selected_query = ref<Report | null>(null);

const is_reading_mode_shown = computed(
    () =>
        (report_state.value === "report-saved" || report_state.value === "result-preview") &&
        !is_loading.value,
);

const { current_fault, current_success, notifyFault, notifySuccess, clearFeedbacks } =
    useFeedbacks();
provide(NOTIFY_FAULT, notifyFault);
provide(CLEAR_FEEDBACKS, clearFeedbacks);
const has_error = computed<boolean>(() => current_fault.value.isValue());

const is_export_allowed = computed<boolean>(() => {
    if (report_state.value !== "report-saved" || has_error.value === true) {
        return false;
    }
    if (!is_user_admin) {
        return true;
    }
    return current_fault.value.isNothing();
});

provide(IS_EXPORT_ALLOWED, is_export_allowed);

function initReports(): void {
    props.reading_cross_tracker_report.duplicateFromReport(props.backend_cross_tracker_report);
    props.writing_cross_tracker_report.duplicateFromReport(props.reading_cross_tracker_report);
}

function loadBackendReport(): void {
    is_loading.value = true;
    getReports(report_id)
        .match(
            (reports: ReadonlyArray<Report>) => {
                queries.value = reports;
                if (reports.length === 0) {
                    selected_query.value = null;
                    props.backend_cross_tracker_report.init("");
                    initReports();
                    if (is_user_admin) {
                        report_state.value = "edit-query";
                    }

                    return;
                }
                selected_query.value = reports[0];
                props.backend_cross_tracker_report.init(reports[0].expert_query);
                initReports();
            },
            (fault) => {
                notifyFault(ReportRetrievalFault(fault));
            },
        )
        .then(() => {
            is_loading.value = false;
        });
}

onMounted(() => {
    loadBackendReport();
    emitter.on(SWITCH_QUERY_EVENT, handleSwitchQuery);
});

onBeforeUnmount(() => {
    emitter.off(SWITCH_QUERY_EVENT);
});

function handleSwitchWriting(): void {
    if (!is_user_admin) {
        return;
    }

    props.writing_cross_tracker_report.duplicateFromReport(props.reading_cross_tracker_report);
    report_state.value = "edit-query";
    clearFeedbacks();
}

function handleSwitchQuery(): void {
    if (!is_user_admin) {
        return;
    }

    clearFeedbacks();
    // Dummy report state to make sure the change is taken in account after changing the selected query
    // This will be removed later when the report_state will be removed or reworked
    report_state.value = "report-saved";
    report_state.value = "result-preview";
}

function handlePreviewResult(): void {
    props.reading_cross_tracker_report.duplicateFromWritingReport(
        props.writing_cross_tracker_report,
    );
    report_state.value = "result-preview";
    clearFeedbacks();
}

function handleCancelQueryEdition(): void {
    props.reading_cross_tracker_report.duplicateFromReport(props.backend_cross_tracker_report);
    report_state.value = "report-saved";
    clearFeedbacks();
}

function reportSaved(): void {
    initReports();
    report_state.value = "report-saved";
    clearFeedbacks();
    notifySuccess(gettext_provider.$gettext("Report has been successfully saved"));
}

function unsavedReportDiscarded(): void {
    initReports();
    report_state.value = "report-saved";
    clearFeedbacks();
}

defineExpose({
    report_state,
    current_fault,
    current_success,
    is_export_allowed,
});
</script>

<style lang="scss" scoped>
.reading-mode-shown {
    border: 0;
}

.action-buttons {
    margin: 0 0 var(--tlp-medium-spacing);
}
</style>
