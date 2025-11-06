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
    <empty-state v-if="total === 0" data-test="empty-state" />
    <div class="overflow-wrapper" v-else data-test="selectable-table">
        <div class="selectable-table">
            <selectable-table-header v-bind:columns="table_state.columns" />
            <selectable-table-content v-bind:table_state="table_state" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { provide } from "vue";
import { ARROW_DATA_STORE } from "../../injection-symbols";

import EmptyState from "../EmptyState.vue";
import { ArrowDataStore } from "../../domain/ArrowDataStore";
import SelectableTableHeader from "./SelectableTableHeader.vue";
import SelectableTableContent from "./SelectableTableContent.vue";
import type { TableDataState } from "../TableWrapper.vue";

provide(ARROW_DATA_STORE, ArrowDataStore());

const props = defineProps<{
    table_state: TableDataState;
    total: number;
}>();

const number_of_selected_columns = numberOfSelectedColumnsMinusTheAtArtifactColumn();

function numberOfSelectedColumnsMinusTheAtArtifactColumn(): number {
    return props.table_state.columns.size - 1;
}
</script>

<style scoped lang="scss">
@use "../../../themes/cell";
@use "../../../themes/pretty-title";

.overflow-wrapper {
    margin: 0 calc(-1 * var(--tlp-medium-spacing));
    overflow-y: auto;
}

.selectable-table {
    display: grid;
    grid-template-columns:
        [edit] min-content
        repeat(v-bind(number_of_selected_columns), auto);
    grid-template-rows:
        [headers] var(--tlp-x-large-spacing)
        auto;
    font-size: 0.875rem;
}
</style>
