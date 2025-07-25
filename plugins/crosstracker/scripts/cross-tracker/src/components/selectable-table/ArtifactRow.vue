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
    <div class="artifact-row" data-test="artifact-row">
        <edit-cell v-bind:uri="row.uri" />
        <selectable-cell
            v-for="column_name of columns"
            v-bind:key="column_name + '_' + level + '_' + row.id"
            v-bind:cell="row.cells.get(column_name)"
            v-bind:artifact_uri="row.uri"
            v-bind:expected_number_of_forward_link="row.expected_number_of_forward_links"
            v-bind:expected_number_of_reverse_link="row.expected_number_of_reverse_links"
            v-on:toggle-links="toggleLinks"
            v-bind:level="level"
            v-bind:is_last="is_last"
            v-bind:parent_element="parent_element"
            v-bind:parent_caret="parent_caret"
            v-bind:direction="direction"
            v-bind:reverse_links_count="reverse_links_count"
        />
    </div>
    <template v-if="is_expanded && current_element_ref && current_caret_ref">
        <artifact-link-rows
            v-for="(artifact_link, index) in [forward, reverse]"
            v-bind:key="index"
            v-bind:row="row"
            v-bind:columns="columns"
            v-bind:is_loading="artifact_link.is_loading"
            v-bind:expected_number_of_links="artifact_link.expected_number_of_links"
            v-bind:artifact_links_rows="artifact_link.artifact_links"
            v-bind:level="level + 1"
            v-bind:tql_query="tql_query"
            v-bind:parent_element="current_element_ref"
            v-bind:parent_caret="current_caret_ref"
            v-bind:direction="artifact_link.direction"
            v-bind:reverse_links_count="reverse.artifact_links.length"
            v-bind:ancestors="[...ancestors, row.id]"
        />
        <row-error-message v-if="error_message !== ''" v-bind:error_message="error_message" />
        <row-load-all-button v-if="display_a_load_all_button" v-on:click="loadAllArtifactLinks" />
    </template>
</template>
<script setup lang="ts">
import { computed, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Fault } from "@tuleap/fault";
import type {
    ArtifactLinkDirection,
    ArtifactRow,
    ArtifactsTable,
} from "../../domain/ArtifactsTable";
import { MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED } from "../../api/ArtifactLinksRetriever";
import type { ArtifactsTableWithTotal } from "../../domain/RetrieveArtifactsTable";
import { FORWARD_DIRECTION, REVERSE_DIRECTION } from "../../domain/ArtifactsTable";
import RowErrorMessage from "../feedback/RowErrorMessage.vue";
import RowLoadAllButton from "../feedback/RowLoadAllButton.vue";
import { RETRIEVE_ARTIFACT_LINKS, WIDGET_ID } from "../../injection-symbols";
import ArtifactLinkRows from "./ArtifactLinkRows.vue";
import SelectableCell from "./SelectableCell.vue";
import EditCell from "./EditCell.vue";

interface ArtifactLinksFetchStatus {
    is_loading: boolean;
    artifact_links: ReadonlyArray<ArtifactRow>;
    expected_number_of_links: number;
    direction: ArtifactLinkDirection;
}

const props = defineProps<{
    row: ArtifactRow;
    columns: ArtifactsTable["columns"];
    tql_query: string;
    level: number;
    is_last: boolean;
    parent_element: HTMLElement | undefined;
    parent_caret: HTMLElement | undefined;
    direction: ArtifactLinkDirection | undefined;
    reverse_links_count: number | undefined;
    ancestors: number[];
}>();

const DIRECT_PARENT = 1;
const artifact_links_retriever = strictInject(RETRIEVE_ARTIFACT_LINKS);
const widget_id = strictInject(WIDGET_ID);

const forward_links = ref<ReadonlyArray<ArtifactRow>>([]);
const reverse_links = ref<ReadonlyArray<ArtifactRow>>([]);
const are_forward_links_loading = ref(true);
const are_reverse_links_loading = ref(true);
const is_expanded = ref(false);
const error_message = ref("");
const total_number_of_forward_links = ref(0);
const total_number_of_reverse_links = ref(0);

const current_element_ref = ref<HTMLElement>();
const current_caret_ref = ref<HTMLElement>();

const forward = computed((): ArtifactLinksFetchStatus => {
    return {
        is_loading: are_forward_links_loading.value,
        artifact_links: forward_links.value,
        expected_number_of_links: props.row.expected_number_of_forward_links,
        direction: FORWARD_DIRECTION,
    };
});

const reverse = computed((): ArtifactLinksFetchStatus => {
    return {
        is_loading: are_reverse_links_loading.value,
        artifact_links: reverse_links.value,
        expected_number_of_links: props.row.expected_number_of_reverse_links,
        direction: REVERSE_DIRECTION,
    };
});

const display_a_load_all_button = computed((): boolean => {
    return totalNumberOfLinksIsGreaterThanMaximalLimit() && linksAreNotAllLoaded();
});

function totalNumberOfLinksIsGreaterThanMaximalLimit(): boolean {
    return (
        total_number_of_forward_links.value > MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED ||
        total_number_of_reverse_links.value > MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED
    );
}

function linksAreNotAllLoaded(): boolean {
    if (props.level === 0) {
        return (
            total_number_of_forward_links.value !== forward.value.artifact_links.length ||
            total_number_of_reverse_links.value !== reverse.value.artifact_links.length
        );
    }
    return (
        total_number_of_forward_links.value +
            total_number_of_reverse_links.value -
            DIRECT_PARENT !==
        forward.value.artifact_links.length + reverse.value.artifact_links.length
    );
}

function toggleLinks(current_element: HTMLElement, current_caret: HTMLElement): void {
    current_element_ref.value = current_element;
    current_caret_ref.value = current_caret;

    is_expanded.value = !is_expanded.value;

    if (!is_expanded.value) {
        return;
    }

    if (!are_forward_links_loading.value && !are_reverse_links_loading.value) {
        return;
    }

    artifact_links_retriever
        .getForwardLinks(widget_id, props.row.id, props.tql_query)
        .match(
            (artifacts: ArtifactsTableWithTotal) => {
                total_number_of_forward_links.value = artifacts.total;
                forward_links.value = artifacts.table.rows.filter(
                    (row) => row.id !== props.ancestors.slice(-1)[0],
                );
            },
            (fault: Fault) => {
                error_message.value = String(fault);
            },
        )
        .then(() => {
            are_forward_links_loading.value = false;
        });

    artifact_links_retriever
        .getReverseLinks(widget_id, props.row.id, props.tql_query)
        .match(
            (artifacts: ArtifactsTableWithTotal) => {
                total_number_of_reverse_links.value = artifacts.total;
                reverse_links.value = artifacts.table.rows.filter(
                    (row) => row.id !== props.ancestors.slice(-1)[0],
                );
            },
            (fault: Fault) => {
                error_message.value = String(fault);
            },
        )
        .then(() => {
            are_reverse_links_loading.value = false;
        });
}

function loadAllArtifactLinks(): void {
    artifact_links_retriever
        .getAllForwardLinks(widget_id, props.row.id, props.tql_query)
        .match(
            (artifacts: ArtifactsTable[]) => {
                if (artifacts.length === 0) {
                    return;
                }
                const rows: ArtifactRow[] = [];
                for (const artifact of artifacts) {
                    if (artifact.rows) {
                        rows.push(...artifact.rows);
                    }
                }
                forward_links.value = rows.filter((row) => row.id !== props.ancestors.slice(-1)[0]);
            },
            (fault: Fault) => {
                error_message.value = String(fault);
            },
        )
        .then(() => {
            are_forward_links_loading.value = false;
        });

    artifact_links_retriever
        .getAllReverseLinks(widget_id, props.row.id, props.tql_query)
        .match(
            (artifacts: ArtifactsTable[]) => {
                if (artifacts.length === 0) {
                    return;
                }
                const rows: ArtifactRow[] = [];
                for (const artifact of artifacts) {
                    if (artifact.rows) {
                        rows.push(...artifact.rows);
                    }
                }
                reverse_links.value = rows.filter((row) => row.id !== props.ancestors.slice(-1)[0]);
            },
            (fault: Fault) => {
                error_message.value = String(fault);
            },
        )
        .then(() => {
            are_reverse_links_loading.value = false;
        });
}
</script>
<style scoped lang="scss">
.artifact-row {
    display: grid;
    grid-column: 1 / -1;
    grid-template-columns: subgrid;
}

.artifact-row:nth-of-type(even) {
    background: var(--tlp-table-row-background-even);
}

.artifact-row:nth-of-type(odd) {
    background: var(--tlp-table-row-background-odd);
}
</style>
