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
    <edit-cell v-bind:uri="row.uri" v-bind:even="is_even" />
    <selectable-cell
        v-for="(column_name, column_index) of columns"
        v-bind:key="column_name + '_' + level + '_' + row.id"
        v-bind:cell="row.cells.get(column_name)"
        v-bind:artifact_uri="row.uri"
        v-bind:number_of_forward_link="row.number_of_forward_link"
        v-bind:number_of_reverse_link="row.number_of_reverse_link"
        v-bind:even="is_even"
        v-bind:last_of_row="isLastCellOfRow(column_index, columns.size)"
        v-on:toggle-links="toggleLinks(row)"
        v-bind:level="level"
    />
    <template v-if="is_expanded">
        <artifact-link-rows
            v-for="(artifact_link, index) in [forward, reverse]"
            v-bind:key="index"
            v-bind:row="row"
            v-bind:columns="columns"
            v-bind:is_loading="artifact_link.is_loading"
            v-bind:number_of_link="artifact_link.number_of_links"
            v-bind:artifact_links_rows="artifact_link.artifact_links"
            v-bind:level="level + 1"
            v-bind:query_id="query_id"
        />
    </template>
</template>
<script setup lang="ts">
import { ref, computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { ArtifactsTable, ArtifactRow } from "../../domain/ArtifactsTable";
import type { ArtifactsTableWithTotal } from "../../domain/RetrieveArtifactsTable";
import { RETRIEVE_ARTIFACT_LINKS } from "../../injection-symbols";
import ArtifactLinkRows from "./ArtifactLinkRows.vue";
import SelectableCell from "./SelectableCell.vue";
import EditCell from "./EditCell.vue";

interface ArtifactLinksFetchStatus {
    is_loading: boolean;
    artifact_links: ReadonlyArray<ArtifactRow>;
    number_of_links: number;
}

const props = defineProps<{
    row: ArtifactRow;
    columns: ArtifactsTable["columns"];
    query_id: string;
    level: number;
    is_even: boolean;
}>();

const artifact_links_retriever = strictInject(RETRIEVE_ARTIFACT_LINKS);

const forward_links = ref<ReadonlyArray<ArtifactRow>>([]);
const reverse_links = ref<ReadonlyArray<ArtifactRow>>([]);
const are_forward_links_loading = ref(true);
const are_reverse_links_loading = ref(true);
const is_expanded = ref(false);

const forward = computed((): ArtifactLinksFetchStatus => {
    return {
        is_loading: are_forward_links_loading.value,
        artifact_links: forward_links.value,
        number_of_links: props.row.number_of_forward_link,
    };
});

const reverse = computed((): ArtifactLinksFetchStatus => {
    return {
        is_loading: are_reverse_links_loading.value,
        artifact_links: reverse_links.value,
        number_of_links: props.row.number_of_reverse_link,
    };
});

function toggleLinks(row: ArtifactRow): void {
    is_expanded.value = !is_expanded.value;

    if (!is_expanded.value) {
        return;
    }

    artifact_links_retriever
        .getForwardLinks(props.query_id, row.id)
        .map((artifacts: ArtifactsTableWithTotal) => {
            forward_links.value = artifacts.table.rows;
        })
        .then(() => {
            are_forward_links_loading.value = false;
        });

    artifact_links_retriever
        .getReverseLinks(props.query_id, row.id)
        .map((artifacts: ArtifactsTableWithTotal) => {
            reverse_links.value = artifacts.table.rows;
        })
        .then(() => {
            are_reverse_links_loading.value = false;
        });
}

function isLastCellOfRow(index: number, size: number): boolean {
    return index + 1 === size;
}
</script>
