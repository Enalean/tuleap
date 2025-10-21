<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <section class="tlp-pane-section artifact-table" ref="selectable-table">
        <div class="cross-tracker-loader" v-if="is_loading" data-test="loading"></div>
        <template v-else>
            <selectable-table
                v-bind:table_state="table_state"
                v-bind:total="total"
                data-test="selectable-table"
            />
            <selectable-pagination
                v-if="total > 0"
                v-bind:limit="limit"
                v-bind:offset="offset"
                v-bind:total_number="total"
                v-on:new-page="handleNewPage"
                data-test="pagination"
            />
        </template>
    </section>
</template>

<script setup lang="ts">
import SelectableTable from "./selectable-table/SelectableTable.vue";
import { onBeforeUnmount, onMounted, provide, ref, useTemplateRef, watch } from "vue";
import type { ArtifactLinkLoadError, TableDataOrchestrator } from "../domain/TableDataOrchestrator";
import {
    ARROW_REDRAW_TRIGGERER,
    EMITTER,
    TABLE_DATA_ORCHESTRATOR,
    TABLE_DATA_STORE,
    TABLE_WRAPPER_OPERATIONS,
} from "../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import SelectablePagination from "./selectable-table/SelectablePagination.vue";
import type { ArtifactLinkDirection, ArtifactRow, ArtifactsTable } from "../domain/ArtifactsTable";
import { FORWARD_DIRECTION, REVERSE_DIRECTION } from "../domain/ArtifactsTable";
import type { RowEntry, TableDataStore } from "../domain/TableDataStore";
import type { Fault } from "@tuleap/fault";
import type { ArrowRedrawTriggerer } from "../ArrowRedrawTriggerer";
import { NOTIFY_FAULT_EVENT, SEARCH_ARTIFACTS_SUCCESS_EVENT } from "../helpers/widget-events";
import { ArtifactsRetrievalFault } from "../domain/ArtifactsRetrievalFault";

const table_data_orchestrator: TableDataOrchestrator = strictInject(TABLE_DATA_ORCHESTRATOR);
const table_data_store: TableDataStore = strictInject(TABLE_DATA_STORE);
const arrow_redraw_triggerer: ArrowRedrawTriggerer = strictInject(ARROW_REDRAW_TRIGGERER);
const emitter = strictInject(EMITTER);

export type TableWrapperOperations = {
    expandRow(row: ArtifactRow): void;
    collapseRow(row: ArtifactRow): void;
    loadAllArtifacts(row: RowEntry): void;
};

provide(TABLE_WRAPPER_OPERATIONS, {
    expandRow,
    collapseRow,
    loadAllArtifacts,
});

const selectable_table_element = useTemplateRef<HTMLElement>("selectable-table");

export type TableDataState = {
    row_collection: RowEntry[];
    columns: ArtifactsTable["columns"];
    uuids_of_loading_rows: Array<ArtifactLinkRowData>;
    uuids_of_error_rows: Array<ArtifactLinkLoadError>;
};

export type ArtifactLinkRowData = {
    row_uuid: string;
    direction: ArtifactLinkDirection;
};

export type ArtifactLinkRowDataError = ArtifactLinkRowData & {
    error: string;
};

const table_state = ref<TableDataState>({
    row_collection: [],
    columns: new Set(),
    uuids_of_loading_rows: [],
    uuids_of_error_rows: [],
});

const is_loading = ref(false);
let offset = 0;
const limit = 30;
const total = ref(0);

const props = defineProps<{
    tql_query: string;
}>();

watch(
    () => props.tql_query,
    () => {
        loadTopLevelArtifacts();
    },
    { immediate: true },
);

function handleNewPage(new_offset: number): void {
    offset = new_offset;
    loadTopLevelArtifacts();
}

function loadAllArtifacts(row_entry: RowEntry): void {
    const parent_row_entry = table_data_store.retrieveParentOfRow(row_entry);
    table_data_orchestrator
        .loadAllForwardArtifactLinks(parent_row_entry.row, props.tql_query)
        .match(
            (orchestrator_result) => {
                table_state.value.row_collection = orchestrator_result.row_collection;
                table_state.value.columns = orchestrator_result.columns;
            },
            (fault: Fault) => {
                table_state.value.uuids_of_error_rows.push({
                    row_uuid: row_entry.row.row_uuid,
                    error: String(fault),
                    direction: row_entry.row.direction,
                });
            },
        );

    table_data_orchestrator
        .loadAllReverseArtifactLinks(parent_row_entry.row, props.tql_query)
        .match(
            (orchestrator_result) => {
                table_state.value.row_collection = orchestrator_result.row_collection;
                table_state.value.columns = orchestrator_result.columns;
            },
            (fault: Fault) => {
                table_state.value.uuids_of_error_rows.push({
                    row_uuid: row_entry.row.row_uuid,
                    error: String(fault),
                    direction: row_entry.row.direction,
                });
            },
        );
}

function loadTopLevelArtifacts(): void {
    is_loading.value = true;

    if (props.tql_query === "") {
        is_loading.value = false;
        return;
    }

    table_data_orchestrator
        .loadTopLevelArtifacts(
            props.tql_query,
            limit,
            offset,
            () => {
                emitter.emit(SEARCH_ARTIFACTS_SUCCESS_EVENT);
            },
            (fault) => {
                emitter.emit(NOTIFY_FAULT_EVENT, {
                    fault: ArtifactsRetrievalFault(fault),
                    tql_query: props.tql_query,
                });
            },
        )
        .then((orchestrator_result) => {
            table_state.value.row_collection = orchestrator_result.result.row_collection;
            table_state.value.columns = orchestrator_result.result.columns;
            total.value = orchestrator_result.total;
        })
        .finally(() => {
            is_loading.value = false;
        });
}

function removeRowFromLoadingArtifacts(row: ArtifactRow, direction: ArtifactLinkDirection): void {
    table_state.value.uuids_of_loading_rows = table_state.value.uuids_of_loading_rows.filter(
        (loading_row_uuid) =>
            !(
                loading_row_uuid.row_uuid === row.row_uuid &&
                loading_row_uuid.direction === direction
            ),
    );
}

function removeRowFromErrorAtArtifactLinkLoad(row: ArtifactRow): void {
    table_state.value.uuids_of_error_rows = table_state.value.uuids_of_error_rows.filter(
        (error_row) => error_row.row_uuid !== row.row_uuid,
    );
}

function expandRow(row: ArtifactRow): void {
    removeRowFromErrorAtArtifactLinkLoad(row);
    table_state.value.uuids_of_loading_rows.push({
        row_uuid: row.row_uuid,
        direction: FORWARD_DIRECTION,
    });
    table_state.value.uuids_of_loading_rows.push({
        row_uuid: row.row_uuid,
        direction: REVERSE_DIRECTION,
    });

    table_data_orchestrator
        .loadForwardArtifactLinks(row, props.tql_query, (fault) => {
            table_state.value.uuids_of_error_rows.push({
                row_uuid: row.row_uuid,
                error: String(fault),
                direction: FORWARD_DIRECTION,
            });
        })
        .then((orchestrator_result) => {
            table_state.value.row_collection = orchestrator_result.row_collection;
            table_state.value.columns = orchestrator_result.columns;
            removeRowFromLoadingArtifacts(row, FORWARD_DIRECTION);
        });

    table_data_orchestrator
        .loadReverseArtifactLinks(row, props.tql_query, (fault) => {
            table_state.value.uuids_of_error_rows.push({
                row_uuid: row.row_uuid,
                error: String(fault),
                direction: REVERSE_DIRECTION,
            });
        })
        .then((orchestrator_result) => {
            table_state.value.row_collection = orchestrator_result.row_collection;
            table_state.value.columns = orchestrator_result.columns;
            removeRowFromLoadingArtifacts(row, REVERSE_DIRECTION);
        });
}

function collapseRow(row: ArtifactRow): void {
    table_state.value.row_collection = table_data_orchestrator.closeArtifactRow(row).row_collection;
}

onMounted(() => {
    if (!selectable_table_element.value) {
        return;
    }
    arrow_redraw_triggerer.listenToSelectableTableResize(selectable_table_element.value);
});

onBeforeUnmount(() => {
    if (!selectable_table_element.value) {
        return;
    }
    arrow_redraw_triggerer.removeListener(selectable_table_element.value);
});
</script>

<style scoped lang="scss">
.artifact-table {
    position: relative;
}
</style>
