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
    <span
        class="headers-cell"
        v-for="(column_name, column_index) of columns"
        v-bind:key="column_name"
        v-bind:class="{
            'is-last-cell-of-row': isLastCellOfRow(column_index, columns.size),
            'is-pretty-title-column': column_name === PRETTY_TITLE_COLUMN_NAME,
        }"
        data-test="column-header"
        >{{ getColumnName(column_name) }}</span
    >
</template>
<script setup lang="ts">
import type { ColumnName } from "../../domain/ColumnName";
import { PRETTY_TITLE_COLUMN_NAME } from "../../domain/ColumnName";
import type { ArtifactsTable } from "../../domain/ArtifactsTable";
import type { GetColumnName } from "../../domain/ColumnNameGetter";
import { strictInject } from "@tuleap/vue-strict-inject";
import { GET_COLUMN_NAME } from "../../injection-symbols";

defineProps<{
    columns: ArtifactsTable["columns"];
}>();

const column_name_getter: GetColumnName = strictInject(GET_COLUMN_NAME);

const getColumnName = (name: ColumnName): string => {
    return column_name_getter.getTranslatedColumnName(name);
};

function isLastCellOfRow(index: number, size: number): boolean {
    return index + 1 === size;
}
</script>
<style scoped lang="scss">
@use "../../../themes/cell";
@use "../../../themes/pretty-title";

.headers-cell {
    @include cell.cell-template;

    grid-row: headers;
    border-bottom: 2px solid var(--tlp-main-color);
    color: var(--tlp-main-color);
    white-space: nowrap;
}

.is-pretty-title-column {
    @include pretty-title.is-pretty-title-column;
}
</style>
