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
    <row-artifact
        v-for="(row, index) of rows"
        v-bind:key="row.artifact_id + level"
        v-bind:row="row"
        v-bind:level="level"
        v-bind:tql_query="tql_query"
        v-bind:is_last="rows.length === index + 1"
        v-bind:parent_element="parent_element"
        v-bind:parent_caret="parent_caret"
        v-bind:reverse_links_count="reverse_links_count"
        v-bind:ancestors="ancestors"
        v-bind:parent_row="parent_row"
    />
</template>

<script setup lang="ts">
import RowArtifact from "./RowArtifact.vue";
import type { ArtifactsTable, ArtifactRow } from "../../domain/ArtifactsTable";

defineProps<{
    rows: ArtifactsTable["rows"];
    tql_query: string;
    level: number;
    parent_element?: HTMLElement;
    parent_caret?: HTMLElement;
    reverse_links_count?: number;
    ancestors: number[];
    parent_row: ArtifactRow | null;
}>();
</script>
