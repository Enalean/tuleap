<!--
  - Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
    <empty-state
        v-if="is_table_empty"
        v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
    />
    <div class="cross-tracker-loader" v-if="is_loading" data-test="loading"></div>
    <div class="overflow-wrapper" v-if="total > 0">
        <div class="selectable-table" v-if="!is_loading">
            <span
                class="headers-cell"
                v-for="(column_name, column_index) of columns"
                v-bind:key="column_name"
                v-bind:class="{
                    'is-last-cell-of-row': isLastCellOfRow(column_index, columns.size),
                }"
                data-test="column-header"
                >{{ getColumnName(column_name) }}</span
            >
            <template v-for="(row, index) of rows" v-bind:key="row.uri">
                <edit-cell v-bind:uri="row.uri" v-bind:even="isEven(index)" />
                <selectable-cell
                    v-for="(column_name, column_index) of columns"
                    v-bind:key="column_name + index"
                    v-bind:cell="row.cells.get(column_name)"
                    v-bind:artifact_uri="row.uri"
                    v-bind:even="isEven(index)"
                    v-bind:last_of_row="isLastCellOfRow(column_index, columns.size)"
                />
            </template>
        </div>
    </div>
    <selectable-pagination
        v-bind:limit="limit"
        v-bind:offset="offset"
        v-bind:total_number="total"
        v-on:new-page="handleNewPage"
    />
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { NOTIFY_FAULT, REPORT_STATE, RETRIEVE_ARTIFACTS_TABLE } from "../../injection-symbols";
import type WritingCrossTrackerReport from "../../writing-mode/writing-cross-tracker-report";
import type { ArtifactsTable } from "../../domain/ArtifactsTable";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { useGettext } from "vue3-gettext";
import type { ArtifactsTableWithTotal } from "../../domain/RetrieveArtifactsTable";
import SelectablePagination from "./SelectablePagination.vue";
import EmptyState from "../EmptyState.vue";
import { ArtifactsRetrievalFault } from "../../domain/ArtifactsRetrievalFault";
import SelectableCell from "./SelectableCell.vue";
import type { ColumnName } from "../../domain/ColumnName";
import {
    ARTIFACT_COLUMN_NAME,
    ARTIFACT_ID_COLUMN_NAME,
    ASSIGNED_TO_COLUMN_NAME,
    DESCRIPTION_COLUMN_NAME,
    LAST_UPDATE_BY_COLUMN_NAME,
    LAST_UPDATE_DATE_COLUMN_NAME,
    PRETTY_TITLE_COLUMN_NAME,
    PROJECT_COLUMN_NAME,
    STATUS_COLUMN_NAME,
    SUBMITTED_BY_COLUMN_NAME,
    SUBMITTED_ON_COLUMN_NAME,
    TITLE_COLUMN_NAME,
    TRACKER_COLUMN_NAME,
} from "../../domain/ColumnName";
import EditCell from "./EditCell.vue";

const { $gettext } = useGettext();

const artifacts_retriever = strictInject(RETRIEVE_ARTIFACTS_TABLE);
const report_state = strictInject(REPORT_STATE);
const notifyFault = strictInject(NOTIFY_FAULT);

const props = defineProps<{
    writing_cross_tracker_report: WritingCrossTrackerReport;
}>();

const is_loading = ref(false);
const columns = ref<ArtifactsTable["columns"]>(new Set());
const rows = ref<ArtifactsTable["rows"]>([]);
const total = ref(0);
let offset = 0;
const limit = 30;

const is_table_empty = computed<boolean>(() => !is_loading.value && total.value === 0);

watch(report_state, () => {
    if (report_state.value === "report-saved" || report_state.value === "result-preview") {
        refreshArtifactList();
    }
});

function handleNewPage(new_offset: number): void {
    offset = new_offset;
    refreshArtifactList();
}

function refreshArtifactList(): void {
    if (!props.writing_cross_tracker_report.expert_mode) {
        return;
    }

    rows.value = [];
    columns.value = new Set<string>();
    is_loading.value = true;
    loadArtifacts();
}

onMounted(() => {
    refreshArtifactList();
});

function loadArtifacts(): void {
    getArtifactsFromReportOrUnsavedQuery()
        .match(
            (report_with_total) => {
                columns.value = report_with_total.table.columns;
                rows.value = report_with_total.table.rows;
                total.value = report_with_total.total;
            },
            (fault) => {
                notifyFault(ArtifactsRetrievalFault(fault));
            },
        )
        .then(() => {
            is_loading.value = false;
        });
}

function getArtifactsFromReportOrUnsavedQuery(): ResultAsync<ArtifactsTableWithTotal, Fault> {
    if (report_state.value === "report-saved") {
        return artifacts_retriever.getSelectableReportContent(limit, offset);
    }

    return artifacts_retriever.getSelectableQueryResult(
        props.writing_cross_tracker_report.getTrackerIds(),
        props.writing_cross_tracker_report.expert_query,
        props.writing_cross_tracker_report.expert_mode,
        limit,
        offset,
    );
}

const getColumnName = (name: ColumnName): string => {
    if (name === TITLE_COLUMN_NAME) {
        return $gettext("Title");
    }
    if (name === DESCRIPTION_COLUMN_NAME) {
        return $gettext("Description");
    }
    if (name === STATUS_COLUMN_NAME) {
        return $gettext("Status");
    }
    if (name === ASSIGNED_TO_COLUMN_NAME) {
        return $gettext("Assigned to");
    }
    if (name === ARTIFACT_ID_COLUMN_NAME) {
        return $gettext("Id");
    }
    if (name === SUBMITTED_ON_COLUMN_NAME) {
        return $gettext("Submitted on");
    }
    if (name === SUBMITTED_BY_COLUMN_NAME) {
        return $gettext("Submitted by");
    }
    if (name === LAST_UPDATE_DATE_COLUMN_NAME) {
        return $gettext("Last update date");
    }
    if (name === LAST_UPDATE_BY_COLUMN_NAME) {
        return $gettext("Last update by");
    }
    if (name === PROJECT_COLUMN_NAME) {
        return $gettext("Project");
    }
    if (name === TRACKER_COLUMN_NAME) {
        return $gettext("Tracker");
    }
    if (name === PRETTY_TITLE_COLUMN_NAME) {
        return $gettext("Artifact");
    }
    if (name === ARTIFACT_COLUMN_NAME) {
        return "";
    }
    return name;
};

const isEven = (index: number): boolean => index % 2 === 0;

function isLastCellOfRow(index: number, size: number): boolean {
    return index + 1 === size;
}
</script>

<style scoped lang="scss">
@use "../../../themes/cell";

.export-button-box {
    margin: var(--tlp-medium-spacing) 0 0 0;
}

.overflow-wrapper {
    margin: 0 calc(-1 * var(--tlp-medium-spacing));
    overflow-y: auto;
}

.selectable-table {
    display: grid;
    grid-template-columns:
        [edit] min-content
        auto;
    grid-template-rows:
        [headers] var(--tlp-x-large-spacing)
        auto;
    font-size: 0.875rem;
}

.headers-cell {
    @include cell.cell-template;

    grid-row: headers;
    border-bottom: 2px solid var(--tlp-main-color);
    color: var(--tlp-main-color);
    white-space: nowrap;
}
</style>
