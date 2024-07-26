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
        v-if="total === 0 && !is_loading"
        v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
    />
    <div class="cross-tracker-loader" v-if="is_loading" data-test="loading"></div>
    <div class="overflow-wrapper" v-if="total > 0">
        <div class="selectable-table" v-if="!is_loading">
            <span
                class="headers-cell"
                v-for="column_name of columns"
                v-bind:key="column_name"
                data-test="column-header"
                >{{ column_name }}</span
            >
            <template v-for="(row_map, index) of rows">
                <span
                    v-for="column_name of columns"
                    v-bind:key="column_name + index"
                    class="cell"
                    v-bind:class="{
                        'even-row': isEvenRow(index),
                        'odd-row': !isEvenRow(index),
                    }"
                    data-test="cell"
                    v-dompurify-html="renderCell(row_map, column_name)"
                ></span>
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
import { onMounted, ref, watch } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    DATE_FORMATTER,
    DATE_TIME_FORMATTER,
    NOTIFY_FAULT,
    REPORT_STATE,
    RETRIEVE_ARTIFACTS_TABLE,
} from "../../injection-symbols";
import type WritingCrossTrackerReport from "../../writing-mode/writing-cross-tracker-report";
import type { ArtifactRow, ArtifactsTable } from "../../domain/ArtifactsTable";
import { DATE_CELL, NUMERIC_CELL, TEXT_CELL } from "../../domain/ArtifactsTable";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { ArtifactsTableWithTotal } from "../../domain/RetrieveArtifactsTable";
import SelectablePagination from "./SelectablePagination.vue";
import EmptyState from "./EmptyState.vue";

const artifacts_retriever = strictInject(RETRIEVE_ARTIFACTS_TABLE);
const date_formatter = strictInject(DATE_FORMATTER);
const date_time_formatter = strictInject(DATE_TIME_FORMATTER);
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
    rows.value = [];
    columns.value = new Set<string>();
    is_loading.value = true;
    loadArtifacts();
}

onMounted(() => {
    is_loading.value = true;
    loadArtifacts();
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
                notifyFault(fault);
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
        limit,
        offset,
    );
}

function renderCell(row: ArtifactRow, column_name: string): string {
    const cell = row.get(column_name);
    if (!cell) {
        return "";
    }
    if (cell.type === DATE_CELL) {
        const formatter = cell.with_time ? date_time_formatter : date_formatter;
        return cell.value.mapOr(formatter.format, "");
    } else if (cell.type === NUMERIC_CELL) {
        return String(cell.value.unwrapOr(""));
    } else if (cell.type === TEXT_CELL) {
        return cell.value.unwrapOr("");
    }
    return "";
}

function isEvenRow(index: number): boolean {
    return index % 2 === 0;
}
</script>

<style scoped lang="scss">
.overflow-wrapper {
    overflow-y: auto;
}

.selectable-table {
    display: grid;
    grid-template-columns: auto;
    grid-template-rows:
        [headers] var(--tlp-x-large-spacing)
        auto;
    margin: var(--tlp-medium-spacing) var(--tlp-medium-spacing) 0;
}

@mixin -cell-template {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: calc(
        8px - (4px * var(--tlp-is-condensed))
    ); // Match empty cells 32px height (or 24px in condensed mode)
}

.headers-cell {
    @include -cell-template;

    grid-row: headers;
    border-bottom: 2px solid var(--tlp-main-color);
    color: var(--tlp-main-color);
}

.cell {
    @include -cell-template;

    min-height: var(--tlp-x-large-spacing);
}

.even-row {
    background: var(--tlp-table-row-background-even);
}

.odd-row {
    background: var(--tlp-table-row-background-odd);
}
</style>
