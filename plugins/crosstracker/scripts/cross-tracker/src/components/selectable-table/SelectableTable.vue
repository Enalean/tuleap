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
    <empty-state v-if="is_table_empty" v-bind:writing_query="writing_query" />
    <div class="tlp-table-actions" v-if="should_show_export_button">
        <export-x-l-s-x-button v-bind:current_query="writing_query" />
    </div>
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
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    EMITTER,
    GET_COLUMN_NAME,
    IS_EXPORT_ALLOWED,
    NOTIFY_FAULT,
    REPORT_STATE,
    RETRIEVE_ARTIFACTS_TABLE,
} from "../../injection-symbols";
import type { ArtifactsTable } from "../../domain/ArtifactsTable";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { ArtifactsTableWithTotal } from "../../domain/RetrieveArtifactsTable";
import SelectablePagination from "./SelectablePagination.vue";
import EmptyState from "../EmptyState.vue";
import { ArtifactsRetrievalFault } from "../../domain/ArtifactsRetrievalFault";
import SelectableCell from "./SelectableCell.vue";
import type { ColumnName } from "../../domain/ColumnName";
import EditCell from "./EditCell.vue";
import ExportXLSXButton from "../ExportXLSXButton.vue";
import type { RefreshArtifactsEvent } from "../../helpers/emitter-provider";
import { REFRESH_ARTIFACTS_EVENT } from "../../helpers/emitter-provider";
import type { Query } from "../../type";

const column_name_getter = strictInject(GET_COLUMN_NAME);

const artifacts_retriever = strictInject(RETRIEVE_ARTIFACTS_TABLE);
const report_state = strictInject(REPORT_STATE);
const notifyFault = strictInject(NOTIFY_FAULT);
const is_xslx_export_allowed = strictInject(IS_EXPORT_ALLOWED);

const props = defineProps<{
    writing_query: Query;
}>();

const is_loading = ref(false);
const columns = ref<ArtifactsTable["columns"]>(new Set());
const rows = ref<ArtifactsTable["rows"]>([]);
const total = ref(0);
let offset = 0;
const limit = 30;

const is_table_empty = computed<boolean>(() => !is_loading.value && total.value === 0);

const should_show_export_button = computed(
    () => is_xslx_export_allowed.value && !is_table_empty.value,
);

const emitter = strictInject(EMITTER);

function handleNewPage(new_offset: number): void {
    offset = new_offset;
    refreshArtifactList();
}

function refreshArtifactList(): void {
    resetArtifactList();
    loadArtifacts();
}

function resetArtifactList(): void {
    rows.value = [];
    columns.value = new Set<string>();
    is_loading.value = true;
}

onMounted(() => {
    refreshArtifactList();
    emitter.on(REFRESH_ARTIFACTS_EVENT, handleRefreshArtifactsEvent);
});

onBeforeUnmount(() => {
    emitter.off(REFRESH_ARTIFACTS_EVENT);
});

function loadArtifacts(): void {
    if (props.writing_query.tql_query === "") {
        is_loading.value = false;
        return;
    }
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

function handleRefreshArtifactsEvent(event: RefreshArtifactsEvent): void {
    resetArtifactList();
    artifacts_retriever
        .getSelectableQueryResult(event.query.tql_query, limit, offset)
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
        return artifacts_retriever.getSelectableReportContent(
            props.writing_query.id,
            limit,
            offset,
        );
    }

    return artifacts_retriever.getSelectableQueryResult(
        props.writing_query.tql_query,
        limit,
        offset,
    );
}

const getColumnName = (name: ColumnName): string => {
    return column_name_getter.getTranslatedColumnName(name);
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
