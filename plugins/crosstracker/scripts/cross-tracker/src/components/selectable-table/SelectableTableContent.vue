<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <template v-for="row_entry of table_state.row_collection" v-bind:key="row_entry.row.row_uuid">
        <selectable-table-content-row
            v-bind:row_entry="row_entry"
            v-bind:table_state="table_state"
        />
        <row-error-message
            v-if="getErrorMessage(row_entry) !== ''"
            v-bind:error_message="getErrorMessage(row_entry)"
        />
        <load-all-button v-else-if="hasNotLoadedLinks(row_entry)" v-bind:row_entry="row_entry" />
    </template>
</template>
<script setup lang="ts">
import RowErrorMessage from "../feedback/RowErrorMessage.vue";
import LoadAllButton from "../feedback/LoadAllButton.vue";
import SelectableTableContentRow from "./SelectableTableContentRow.vue";
import type { RowEntry } from "../../domain/TableDataStore";
import { isLastVisibleChildWithMoreUnloadedSiblings } from "../../domain/CheckRowHaveDisplayedLinks";
import type { ArtifactLinkRowDataError, TableDataState } from "../TableWrapper.vue";

const props = defineProps<{
    table_state: TableDataState;
}>();

function getErrorMessage(row_entry: RowEntry): string {
    const error_row = props.table_state.uuids_of_error_rows.find(
        (error_row: ArtifactLinkRowDataError) => error_row.row_uuid === row_entry.row.row_uuid,
    );

    if (error_row === undefined) {
        return "";
    }

    return error_row.error;
}

function hasNotLoadedLinks(row_entry: RowEntry): boolean {
    return isLastVisibleChildWithMoreUnloadedSiblings(
        row_entry,
        props.table_state.row_collection,
        props.table_state.uuids_of_loading_rows,
    );
}
</script>
