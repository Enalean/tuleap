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
            v-for="column_name of columns"
            v-bind:key="column_name + index"
            v-bind:cell="row.cells.get(column_name)"
            v-bind:level="level"
        />
    </template>
</template>

<script setup lang="ts">
import { MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED } from "../../../api/ArtifactLinksRetriever";
import type { ArtifactRow, ArtifactsTable } from "../../../domain/ArtifactsTable";
import EmptySelectableCell from "./EmptySelectableCell.vue";
import EmptyEditCell from "./EmptyEditCell.vue";

defineProps<{
    row: ArtifactRow;
    columns: ArtifactsTable["columns"];
    level: number;
    expected_number_of_links: number;
}>();
</script>
