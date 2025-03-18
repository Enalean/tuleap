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
        <div v-if="is_multiple_query_supported && report_state !== 'edit-query'">
            <action-buttons
                v-bind:backend_query="backend_query"
                v-bind:queries="queries"
                v-bind:are_query_details_toggled="are_query_details_toggled"
            />
        </div>
        <div class="cross-tracker-loader" v-if="is_loading"></div>
        <reading-mode
            v-if="is_reading_mode_shown && are_query_details_shown"
            v-bind:backend_query="backend_query"
            v-bind:reading_query="reading_query"
            v-bind:has_error="has_error"
            v-on:switch-to-writing-mode="handleSwitchWriting"
            v-on:saved="reportSaved"
            v-on:discard-unsaved-report="unsavedReportDiscarded"
        />
        <writing-mode
            v-if="report_state === 'edit-query' && are_query_details_shown"
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
    EMITTER,
    IS_EXPORT_ALLOWED,
    IS_MULTIPLE_QUERY_SUPPORTED,
    IS_USER_ADMIN,
    WIDGET_ID,
    REPORT_STATE,
} from "../injection-symbols";
import { ReportRetrievalFault } from "../domain/ReportRetrievalFault";
import ActionButtons from "../components/actions/ActionButtons.vue";
import type {
    CreatedQueryEvent,
    DeletedQueryEvent,
    SwitchQueryEvent,
    ToggleQueryDetailsEvent,
} from "../helpers/emitter-provider";
import {
    EDIT_QUERY_EVENT,
    REFRESH_ARTIFACTS_EVENT,
    QUERY_DELETED_EVENT,
    UPDATE_WIDGET_TITLE_EVENT,
    TOGGLE_QUERY_DETAILS_EVENT,
    CLEAR_FEEDBACK_EVENT,
    NOTIFY_SUCCESS_EVENT,
    NOTIFY_FAULT_EVENT,
    NEW_QUERY_CREATED_EVENT,
    SWITCH_QUERY_EVENT,
} from "../helpers/emitter-provider";

const emit = defineEmits<{
    (e: "switch-to-create-query-pane"): void;
}>();

const widget_id = strictInject(WIDGET_ID);
const is_user_admin = strictInject(IS_USER_ADMIN);
const emitter = strictInject(EMITTER);
const is_multiple_query_supported = strictInject(IS_MULTIPLE_QUERY_SUPPORTED);

const gettext_provider = useGettext();

const empty_query: Query = { id: "", tql_query: "", title: "", description: "", is_default: false };
const backend_query = ref<Query>(empty_query);
const reading_query = ref<Query>(empty_query);
const writing_query = ref<Query>(empty_query);

const report_state = ref<ReportState>("report-saved");
provide(REPORT_STATE, report_state);
const is_loading = ref(true);
const queries = ref<ReadonlyArray<Query>>([]);

const are_query_details_toggled = ref<boolean>(false);

const are_query_details_shown = computed<boolean>(() =>
    is_multiple_query_supported ? are_query_details_toggled.value : true,
);
const is_reading_mode_shown = computed<boolean>(
    () =>
        (report_state.value === "report-saved" || report_state.value === "result-preview") &&
        !is_loading.value,
);

const has_error = ref<boolean>(false);

const is_export_allowed = computed<boolean>(() => {
    if (report_state.value !== "report-saved" || has_error.value) {
        return false;
    }
    if (!is_user_admin) {
        return true;
    }
    return !has_error.value;
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
                if (is_multiple_query_supported) {
                    backend_query.value = reports.find((query) => query.is_default) ?? reports[0];
                    emitter.emit(UPDATE_WIDGET_TITLE_EVENT, {
                        new_title: backend_query.value.title,
                    });
                }
                initQueries();
                has_error.value = false;
            },
            (fault) => {
                emitter.emit(NOTIFY_FAULT_EVENT, { fault: ReportRetrievalFault(fault) });
                has_error.value = true;
            },
        )
        .then(() => {
            is_loading.value = false;
        });
}

onMounted(() => {
    emitter.on(SWITCH_QUERY_EVENT, handleSwitchQuery);
    emitter.on(NEW_QUERY_CREATED_EVENT, handleAddQuery);
    emitter.on(QUERY_DELETED_EVENT, handleDeleteQuery);
    emitter.on(TOGGLE_QUERY_DETAILS_EVENT, handleToggleQueryDetails);
    loadBackendReport();
});

onBeforeUnmount(() => {
    emitter.off(SWITCH_QUERY_EVENT);
    emitter.off(NEW_QUERY_CREATED_EVENT);
    emitter.off(TOGGLE_QUERY_DETAILS_EVENT);
    emitter.off(QUERY_DELETED_EVENT);
});

function handleToggleQueryDetails(toggle: ToggleQueryDetailsEvent): void {
    are_query_details_toggled.value = toggle.display_query_details;
}

function handleAddQuery(new_query: CreatedQueryEvent): void {
    queries.value = queries.value.concat([new_query.created_query]);
}

function handleDeleteQuery(event: DeletedQueryEvent): void {
    queries.value = queries.value.filter((query) => query.id !== event.deleted_query.id);
    if (queries.value.length === 0) {
        report_state.value = "edit-query";
        if (is_multiple_query_supported) {
            emit("switch-to-create-query-pane");
        }
    } else {
        const query = queries.value[0];
        emitter.emit(REFRESH_ARTIFACTS_EVENT, { query });
        emitter.emit(SWITCH_QUERY_EVENT, { query });
        emitter.emit(UPDATE_WIDGET_TITLE_EVENT, { new_title: query.title });
    }
}

function handleSwitchWriting(): void {
    if (!is_user_admin) {
        return;
    }
    emitter.emit(CLEAR_FEEDBACK_EVENT);
    if (is_multiple_query_supported) {
        emitter.emit(EDIT_QUERY_EVENT, { query_to_edit: backend_query.value });
        return;
    }
    writing_query.value = reading_query.value;
    report_state.value = "edit-query";
}

function handleSwitchQuery(event: SwitchQueryEvent): void {
    backend_query.value = event.query;
    initQueries();

    emitter.emit(CLEAR_FEEDBACK_EVENT);
}

function handlePreviewResult(query: Query): void {
    writing_query.value = query;
    reading_query.value = query;
    report_state.value = "result-preview";
    emitter.emit(CLEAR_FEEDBACK_EVENT);
}

function handleCancelQueryEdition(): void {
    reading_query.value = backend_query.value;
    report_state.value = "report-saved";
    emitter.emit(CLEAR_FEEDBACK_EVENT);
}

function reportSaved(query: Query): void {
    backend_query.value = query;
    initQueries();
    report_state.value = "report-saved";
    emitter.emit(CLEAR_FEEDBACK_EVENT);
    emitter.emit(NOTIFY_SUCCESS_EVENT, {
        message: gettext_provider.$gettext("Report has been successfully saved"),
    });
}

function unsavedReportDiscarded(): void {
    initQueries();
    report_state.value = "report-saved";
    emitter.emit(CLEAR_FEEDBACK_EVENT);
}

defineExpose({
    report_state,
    is_export_allowed,
});
</script>

<style scoped lang="scss">
.reading-mode-shown {
    border: 0;
}
</style>
