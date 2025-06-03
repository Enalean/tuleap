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
    <template v-if="is_loading">
        <artifact-link-row-skeleton
            v-if="row.number_of_forward_link > 0"
            v-bind:row="row"
            v-bind:columns="columns"
            v-bind:link_type="'forward'"
            data-test="forward-link-skeleton"
        />
        <artifact-link-row-skeleton
            v-if="row.number_of_reverse_link > 0"
            v-bind:row="row"
            v-bind:columns="columns"
            v-bind:link_type="'reverse'"
            data-test="reverse-link-skeleton"
        />
    </template>
</template>

<script setup lang="ts">
import { ref } from "vue";
import type { ArtifactRow, ArtifactsTable } from "../../domain/ArtifactsTable";
import ArtifactLinkRowSkeleton from "./skeleton/ArtifactLinkRowSkeleton.vue";

defineProps<{
    row: ArtifactRow;
    columns: ArtifactsTable["columns"];
}>();

const is_loading = ref(true);
</script>
