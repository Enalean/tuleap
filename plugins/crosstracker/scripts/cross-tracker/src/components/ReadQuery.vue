<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
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
            <action-buttons v-bind:backend_query="backend_query" v-bind:queries="queries" />
        </div>
        <feedback-message
            v-bind:current_fault="current_fault"
            v-bind:current_success="current_success"
            v-bind:query="writing_query"
        />
        <div class="cross-tracker-loader" v-if="is_loading"></div>
        <reading-mode
            v-if="is_reading_mode_shown"
            v-bind:backend_query="backend_query"
            v-bind:reading_query="reading_query"
            v-bind:has_error="has_error"
            v-on:switch-to-writing-mode="handleSwitchWriting"
            v-on:saved="reportSaved"
            v-on:discard-unsaved-report="unsavedReportDiscarded"
        />
        <writing-mode
            v-if="report_state === 'edit-query'"
            v-bind:writing_query="writing_query"
            v-bind:backend_query="backend_query"
            v-on:preview-result="handlePreviewResult"
            v-on:cancel-query-edition="handleCancelQueryEdition"
        />
    </section>
    <section class="tlp-pane-section" v-if="!is_loading">
        <selectable-table v-bind:writing_query="writing_query" />
    </section>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, provide, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import ReadingMode from "../components/reading-mode/ReadingMode.vue";
import WritingMode from "../components/writing-mode/WritingMode.vue";
import { getQueries } from "../api/rest-querier";
import type { Query } from "../type";
import SelectableTable from "../components/selectable-table/SelectableTable.vue";
import type { ReportState } from "../domain/ReportState";
import {
    CLEAR_FEEDBACKS,
    EMITTER,
    IS_EXPORT_ALLOWED,
    IS_MULTIPLE_QUERY_SUPPORTED,
    IS_USER_ADMIN,
    NOTIFY_FAULT,
    WIDGET_ID,
    REPORT_STATE,
} from "../injection-symbols";
import { useFeedbacks } from "../composables/useFeedbacks";
import { ReportRetrievalFault } from "../domain/ReportRetrievalFault";
import ActionButtons from "../components/actions/ActionButtons.vue";
import type { SwitchQueryEvent } from "../helpers/emitter-provider";
import { SWITCH_QUERY_EVENT } from "../helpers/emitter-provider";
import FeedbackMessage from "./feedback/FeedbackMessage.vue";

const emit = defineEmits<{
    (e: "switch-to-create-query-pane"): void;
}>();

const widget_id = strictInject(WIDGET_ID);
const is_user_admin = strictInject(IS_USER_ADMIN);
const emitter = strictInject(EMITTER);
const is_multiple_query_supported = strictInject(IS_MULTIPLE_QUERY_SUPPORTED);

const gettext_provider = useGettext();

const empty_query: Query = { id: "", tql_query: "", title: "", description: "" };
const backend_query = ref<Query>(empty_query);
const reading_query = ref<Query>(empty_query);
const writing_query = ref<Query>(empty_query);

const report_state = ref<ReportState>("report-saved");
provide(REPORT_STATE, report_state);
const is_loading = ref(true);
const queries = ref<ReadonlyArray<Query>>([]);

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

function initQueries(): void {
    reading_query.value = backend_query.value;
    writing_query.value = backend_query.value;
}

function loadBackendReport(): void {
    is_loading.value = true;
    getQueries(widget_id)
        .match(
            (reports: ReadonlyArray<Query>) => {
                queries.value = reports;
                if (reports.length === 0) {
                    if (is_user_admin) {
                        report_state.value = "edit-query";
                        if (is_multiple_query_supported) {
                            emit("switch-to-create-query-pane");
                        }
                    }

                    return;
                }
                backend_query.value = reports[0];
                initQueries();
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

    writing_query.value = reading_query.value;
    report_state.value = "edit-query";
    clearFeedbacks();
}

function handleSwitchQuery(event: SwitchQueryEvent): void {
    backend_query.value = event.query;
    initQueries();

    clearFeedbacks();
}

function handlePreviewResult(query: Query): void {
    writing_query.value = query;
    reading_query.value = query;
    report_state.value = "result-preview";
    clearFeedbacks();
}

function handleCancelQueryEdition(): void {
    reading_query.value = backend_query.value;
    report_state.value = "report-saved";
    clearFeedbacks();
}

function reportSaved(query: Query): void {
    backend_query.value = query;
    initQueries();
    report_state.value = "report-saved";
    clearFeedbacks();
    notifySuccess(gettext_provider.$gettext("Report has been successfully saved"));
}

function unsavedReportDiscarded(): void {
    initQueries();
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

<style scoped lang="scss">
.reading-mode-shown {
    border: 0;
}

.action-buttons {
    margin: 0 0 var(--tlp-medium-spacing);
}
</style>
