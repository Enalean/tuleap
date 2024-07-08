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
    <div class="overflow-wrapper">
        <div class="cross-tracker-loader" v-if="is_loading" data-test="loading"></div>
        <div class="selectable-table" v-if="!is_loading">
            <div class="headers-cell" v-for="column_name of columns" v-bind:key="column_name">
                <span class="header-text" data-test="column-header">{{ column_name }}</span>
            </div>
            <!-- eslint-disable-next-line vue/require-v-for-key Columns are all variable, there is nothing static to bind to key -->
            <template v-for="(row_map, index) of rows">
                <!-- eslint-disable vue/valid-v-for eslint is not happy about nested v-for -->
                <div
                    class="cell"
                    v-for="column_name of columns"
                    v-bind:key="column_name"
                    v-bind:class="{ 'even-row': isEvenRow(index), 'odd-row': !isEvenRow(index) }"
                    data-test="cell-row"
                >
                    <!-- eslint-enable vue/valid-v-for eslint is not happy about nested v-for -->
                    <span
                        class="cell-text"
                        data-test="cell"
                        v-dompurify-html="renderCell(row_map, column_name)"
                    >
                    </span>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { useMutations } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import {
    DATE_FORMATTER,
    DATE_TIME_FORMATTER,
    RETRIEVE_ARTIFACTS_TABLE,
} from "../../injection-symbols";
import type WritingCrossTrackerReport from "../../writing-mode/writing-cross-tracker-report";
import type { ArtifactRow, ArtifactsTable } from "../../domain/ArtifactsTable";
import { DATE_CELL, NUMERIC_CELL, TEXT_CELL } from "../../domain/ArtifactsTable";

const artifacts_retriever = strictInject(RETRIEVE_ARTIFACTS_TABLE);
const date_formatter = strictInject(DATE_FORMATTER);
const date_time_formatter = strictInject(DATE_TIME_FORMATTER);

const props = defineProps<{
    writing_cross_tracker_report: WritingCrossTrackerReport;
}>();

const { setErrorMessage } = useMutations(["setErrorMessage"]);
const { $gettext } = useGettext();

const is_loading = ref(false);
const columns = ref<ArtifactsTable["columns"]>(new Set());
const rows = ref<ArtifactsTable["rows"]>([]);
const offset = 0;
const limit = 30;

onMounted(() => {
    is_loading.value = true;
    artifacts_retriever
        .getSelectableQueryResult(
            props.writing_cross_tracker_report.getTrackerIds(),
            props.writing_cross_tracker_report.expert_query,
            limit,
            offset,
        )
        .match(
            (report_with_total) => {
                columns.value = report_with_total.table.columns;
                rows.value = report_with_total.table.rows;
            },
            (fault) => {
                setErrorMessage(
                    $gettext("An error occurred: %{error}", { error: String(fault) }, true),
                );
            },
        )
        .then(() => {
            is_loading.value = false;
        });
});

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
    align-items: center;
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
