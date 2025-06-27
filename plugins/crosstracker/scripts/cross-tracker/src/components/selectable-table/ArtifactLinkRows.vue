<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
    <artifact-link-row-skeleton
        v-if="is_loading"
        v-bind:row="row"
        v-bind:columns="columns"
        v-bind:number_of_link="number_of_link"
        v-bind:level="level"
    />
    <artifact-rows
        v-else
        v-bind:rows="artifact_links_rows"
        v-bind:columns="columns"
        v-bind:level="level"
        v-bind:tql_query="tql_query"
    />
</template>

<script setup lang="ts">
import ArtifactRows from "./ArtifactRows.vue";
import ArtifactLinkRowSkeleton from "./skeleton/ArtifactLinkRowSkeleton.vue";
import type { ArtifactRow, ArtifactsTable } from "../../domain/ArtifactsTable";

defineProps<{
    is_loading: boolean;
    row: ArtifactRow;
    columns: ArtifactsTable["columns"];
    tql_query: string;
    level: number;
    artifact_links_rows: ArtifactsTable["rows"];
    number_of_link: number;
}>();
</script>
