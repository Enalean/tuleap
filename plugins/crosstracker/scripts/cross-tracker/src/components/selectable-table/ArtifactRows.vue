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
    <artifact-row
        v-for="(row, index) of rows"
        v-bind:key="row.id + level"
        v-bind:row="row"
        v-bind:columns="columns"
        v-bind:query_id="query_id"
        v-bind:level="level"
        v-bind:is_even="isEven(index)"
    />
</template>

<script setup lang="ts">
import ArtifactRow from "./ArtifactRow.vue";
import type { ArtifactsTable } from "../../domain/ArtifactsTable";

defineProps<{
    rows: ArtifactsTable["rows"];
    columns: ArtifactsTable["columns"];
    query_id: string;
    level: number;
}>();

const isEven = (index: number): boolean => index % 2 === 0;
</script>
