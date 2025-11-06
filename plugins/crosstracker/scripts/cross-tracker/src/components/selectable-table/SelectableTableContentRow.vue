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
    <div class="artifact-row" data-test="artifact-row">
        <edit-cell v-bind:uri="row_entry.row.artifact_uri" />
        <selectable-cell
            v-for="column_name of props.table_state.columns"
            v-bind:key="column_name + '_' + row_entry.row.row_uuid"
            v-bind:row_entry="row_entry"
            v-bind:cell="row_entry.row.cells.get(column_name)"
            v-bind:table_state="props.table_state"
        />
    </div>
    <template v-if="row_entry && is_fetching_artifact_links">
        <template v-for="(loading_batch, index) in get_loading_batches" v-bind:key="index">
            <artifact-link-row-skeleton
                v-if="is_loading_links_for_direction(loading_batch.direction)"
                v-bind:table_state="table_state"
                v-bind:row_entry="row_entry"
                v-bind:expected_number_of_links="loading_batch.expected_number_of_links"
            />
        </template>
    </template>
</template>
<script setup lang="ts">
import type { RowEntry } from "../../domain/TableDataStore";
import EditCell from "./EditCell.vue";
import SelectableCell from "./SelectableCell.vue";
import { computed } from "vue";
import ArtifactLinkRowSkeleton from "./skeleton/ArtifactLinkRowSkeleton.vue";
import type { TableDataState } from "../TableWrapper.vue";
import type { ArtifactLinkDirection, ArtifactRow } from "../../domain/ArtifactsTable";
import { FORWARD_DIRECTION, REVERSE_DIRECTION } from "../../domain/ArtifactsTable";
import { getNumberOfParent } from "../../domain/NumberOfParentForRowCalculator";

const props = defineProps<{
    row_entry: RowEntry;
    table_state: TableDataState;
}>();

type LoadingBatch = {
    direction: ArtifactLinkDirection;
    expected_number_of_links: number;
};

const is_fetching_artifact_links = computed((): boolean => {
    return (
        is_loading_links_for_direction(FORWARD_DIRECTION) ||
        is_loading_links_for_direction(REVERSE_DIRECTION)
    );
});

const get_loading_batches = computed((): LoadingBatch[] => {
    return computeLoadingBatchesForRow(
        props.row_entry.row,
        getNumberOfParent(props.table_state.row_collection, props.row_entry) >= 1,
    );
});

function computeLoadingBatchesForRow(row: ArtifactRow, has_parent: boolean): LoadingBatch[] {
    let expected_number_of_forward_links = props.row_entry.row.expected_number_of_forward_links;
    let expected_number_of_reverse_links = props.row_entry.row.expected_number_of_forward_links;

    if (has_parent) {
        expected_number_of_forward_links =
            row.direction === REVERSE_DIRECTION
                ? Math.max(0, row.expected_number_of_forward_links - 1)
                : row.expected_number_of_forward_links;
        expected_number_of_reverse_links =
            row.direction === FORWARD_DIRECTION
                ? Math.max(0, row.expected_number_of_reverse_links - 1)
                : row.expected_number_of_reverse_links;
    }

    return [
        {
            direction: FORWARD_DIRECTION,
            expected_number_of_links: expected_number_of_forward_links,
        },
        {
            direction: REVERSE_DIRECTION,
            expected_number_of_links: expected_number_of_reverse_links,
        },
    ];
}

function is_loading_links_for_direction(direction: ArtifactLinkDirection): boolean {
    return props.table_state.uuids_of_loading_rows.some(
        (item) => item.row_uuid === props.row_entry.row.row_uuid && item.direction === direction,
    );
}
</script>
<style scoped lang="scss">
@use "../../../themes/cell";
@use "../../../themes/pretty-title";

.artifact-row {
    display: grid;
    grid-column: 1 / -1;
    grid-template-columns: subgrid;
}

.artifact-row:nth-of-type(even) {
    background: var(--tlp-table-row-background-even);
}

.artifact-row:nth-of-type(odd) {
    background: var(--tlp-table-row-background-odd);
}
</style>
