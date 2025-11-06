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
  -
  -->

<template>
    <template
        v-for="index of Math.min(expected_number_of_links, MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED)"
        v-bind:key="index"
    >
        <empty-edit-cell class="tlp-skeleton-icon" />
        <empty-selectable-cell
            v-for="column_name of table_data_store.getColumns()"
            v-bind:key="column_name + index"
            v-bind:cell="row_entry.row.cells.get(column_name)"
            v-bind:level="level"
        />
    </template>
</template>

<script setup lang="ts">
import { MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED } from "../../../api/ArtifactLinksRetriever";
import EmptySelectableCell from "./EmptySelectableCell.vue";
import EmptyEditCell from "./EmptyEditCell.vue";
import { TABLE_DATA_STORE } from "../../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { RowEntry, TableDataStore } from "../../../domain/TableDataStore";
import { onMounted, ref } from "vue";
import { getNumberOfParent } from "../../../domain/NumberOfParentForRowCalculator";
import type { TableDataState } from "../../TableWrapper.vue";

const table_data_store: TableDataStore = strictInject(TABLE_DATA_STORE);

const props = defineProps<{
    row_entry: RowEntry;
    table_state: TableDataState;
    expected_number_of_links: number;
}>();

const level = ref(0);

onMounted(() => {
    level.value = getNumberOfParent(props.table_state.row_collection, props.row_entry) + 1;
});
</script>
