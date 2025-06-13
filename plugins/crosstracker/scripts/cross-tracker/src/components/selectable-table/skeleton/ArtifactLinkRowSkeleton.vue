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
    <template v-for="index of number_of_link" v-bind:key="row.number_of_forward_link + index">
        <empty-edit-cell class="tlp-skeleton-icon" />
        <empty-selectable-cell
            v-for="column_name of columns"
            v-bind:key="column_name + index"
            v-bind:cell="row.cells.get(column_name)"
            v-bind:level="level"
        />
    </template>
</template>

<script setup lang="ts">
import type { ArtifactRow, ArtifactsTable } from "../../../domain/ArtifactsTable";
import EmptySelectableCell from "./EmptySelectableCell.vue";
import EmptyEditCell from "./EmptyEditCell.vue";

const props = defineProps<{
    row: ArtifactRow;
    columns: ArtifactsTable["columns"];
    link_type: "forward" | "reverse";
    level: number;
}>();

const number_of_link =
    props.link_type === "forward"
        ? props.row.number_of_forward_link
        : props.row.number_of_reverse_link;
</script>
