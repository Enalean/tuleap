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
    <template v-if="are_forward_links_loading">
        <artifact-link-row-skeleton
            v-if="row.number_of_forward_link > 0"
            v-bind:row="row"
            v-bind:columns="columns"
            v-bind:link_type="'forward'"
            v-bind:level="level"
        />
    </template>
    <template v-else>
        <template v-for="(link, index) of forward_links" v-bind:key="link.uri">
            <edit-cell v-bind:uri="link.uri" v-bind:even="false" data-test="edit-cell-icon" />
            <selectable-cell
                v-for="(column_name, column_index) of columns"
                v-bind:key="column_name + index"
                v-bind:cell="link.cells.get(column_name)"
                v-bind:artifact_uri="link.uri"
                v-bind:number_of_forward_link="link.number_of_forward_link"
                v-bind:number_of_reverse_link="link.number_of_reverse_link"
                v-bind:even="false"
                v-bind:last_of_row="isLastCellOfRow(column_index, columns.size)"
                v-on:toggle-links="toggleLinks(link)"
                v-bind:level="level"
            />
            <artifact-link-rows
                v-if="link.is_expanded"
                v-bind:row="link"
                v-bind:columns="columns"
                v-bind:artifact_id="link.id"
                v-bind:query_id="query_id"
                v-bind:level="level + 1"
            />
        </template>
    </template>
    <template v-if="are_reverse_links_loading">
        <artifact-link-row-skeleton
            v-if="row.number_of_reverse_link > 0"
            v-bind:row="row"
            v-bind:columns="columns"
            v-bind:link_type="'reverse'"
            v-bind:level="level"
        />
    </template>
    <template v-else>
        <template v-for="(link, index) of reverse_links" v-bind:key="link.uri">
            <edit-cell v-bind:uri="link.uri" v-bind:even="false" data-test="edit-cell-icon" />
            <selectable-cell
                v-for="(column_name, column_index) of columns"
                v-bind:key="column_name + index"
                v-bind:cell="link.cells.get(column_name)"
                v-bind:artifact_uri="link.uri"
                v-bind:number_of_forward_link="link.number_of_forward_link"
                v-bind:number_of_reverse_link="link.number_of_reverse_link"
                v-bind:even="false"
                v-bind:last_of_row="isLastCellOfRow(column_index, columns.size)"
                v-on:toggle-links="toggleLinks(link)"
                v-bind:level="level"
            />
            <artifact-link-rows
                v-if="link.is_expanded"
                v-bind:row="link"
                v-bind:columns="columns"
                v-bind:artifact_id="link.id"
                v-bind:query_id="query_id"
                v-bind:level="level + 1"
            />
        </template>
    </template>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { ArtifactRow, ArtifactsTable } from "../../domain/ArtifactsTable";
import ArtifactLinkRowSkeleton from "./skeleton/ArtifactLinkRowSkeleton.vue";
import type { ArtifactsTableWithTotal } from "../../domain/RetrieveArtifactsTable";
import EditCell from "./EditCell.vue";
import SelectableCell from "./SelectableCell.vue";
import { RETRIEVE_ARTIFACT_LINKS } from "../../injection-symbols";

const props = defineProps<{
    artifact_id: number;
    query_id: string;
    row: ArtifactRow;
    columns: ArtifactsTable["columns"];
    level: number;
}>();

const forward_links = ref<ReadonlyArray<ArtifactRow>>([]);
const reverse_links = ref<ReadonlyArray<ArtifactRow>>([]);
const are_forward_links_loading = ref(true);
const are_reverse_links_loading = ref(true);

const artifact_links_retriever = strictInject(RETRIEVE_ARTIFACT_LINKS);

onMounted(() => {
    artifact_links_retriever
        .getForwardLinks(props.query_id, props.artifact_id)
        .map((artifacts: ArtifactsTableWithTotal) => {
            forward_links.value = artifacts.table.rows;
        })
        .then(() => {
            are_forward_links_loading.value = false;
        });

    artifact_links_retriever
        .getReverseLinks(props.query_id, props.artifact_id)
        .map((artifacts: ArtifactsTableWithTotal) => {
            reverse_links.value = artifacts.table.rows;
        })
        .then(() => {
            are_reverse_links_loading.value = false;
        });
});

function isLastCellOfRow(index: number, size: number): boolean {
    return index + 1 === size;
}

function toggleLinks(row: ArtifactRow): void {
    row.is_expanded = !row.is_expanded;
}
</script>
