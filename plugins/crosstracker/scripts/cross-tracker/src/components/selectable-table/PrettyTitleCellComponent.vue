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
    <span
        v-if="cell && cell.type === PRETTY_TITLE_CELL"
        data-test="cell"
        ref="pretty-title-cell-element"
    >
        <artifact-link-arrow
            v-if="
                current_arrow_data_entry &&
                row_entry.row.direction !== NO_DIRECTION &&
                get_parent_element !== undefined
            "
            v-bind:row_entry="row_entry"
            v-bind:current_element="current_arrow_data_entry"
            v-bind:parent_element="get_parent_element"
            v-bind:table_state="table_state"
        />
        <caret-indentation v-bind:level="level" />
        <button
            type="button"
            v-on:click="toggleArtifactLinksDisplay()"
            class="caret-button"
            v-bind:aria-hidden="doesNotHaveExpandableLinks()"
            v-bind:disabled="doesNotHaveExpandableLinks()"
            data-test="pretty-title-links-button"
        >
            <i
                v-bind:class="caret_class"
                role="img"
                v-bind:aria-label="caret_aria_label"
                data-test="pretty-title-caret"
                ref="target-caret-element"
            ></i>
        </button>
        <a v-bind:href="artifact_url" class="link cross-reference"
            ><span v-bind:class="getCrossRefBadgeClass(cell)"
                >{{ cell.tracker_name }} #{{ cell.artifact_id }}</span
            >{{ cell.title }}</a
        >
    </span>
</template>

<script setup lang="ts">
import { computed, ref, useTemplateRef, onMounted, onBeforeUnmount } from "vue";
import { useGettext } from "vue3-gettext";
import { NO_DIRECTION, PRETTY_TITLE_CELL } from "../../domain/ArtifactsTable";
import type { Cell, PrettyTitleCell } from "../../domain/ArtifactsTable";
import CaretIndentation from "./CaretIndentation.vue";
import ArtifactLinkArrow from "./ArtifactLinkArrow.vue";
import {
    DASHBOARD_TYPE,
    DASHBOARD_ID,
    ARROW_DATA_STORE,
    TABLE_WRAPPER_OPERATIONS,
} from "../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT_DASHBOARD } from "../../domain/DashboardType";
import type { ArrowDataEntry, ArrowDataStore } from "../../domain/ArrowDataStore";
import { loadTooltips } from "@tuleap/tooltip";
import type { RowEntry } from "../../domain/TableDataStore";
import type { TableDataState, TableWrapperOperations } from "../TableWrapper.vue";
import { hasExpandableLinks } from "../../domain/CheckExpandableLink";
import { getNumberOfParent } from "../../domain/NumberOfParentForRowCalculator";

const dashboard_id = strictInject(DASHBOARD_ID);
const dashboard_type = strictInject(DASHBOARD_TYPE);
const arrow_data_store: ArrowDataStore = strictInject(ARROW_DATA_STORE);
const table_wrapper_operations: TableWrapperOperations = strictInject(TABLE_WRAPPER_OPERATIONS);

const { $gettext } = useGettext();

const props = defineProps<{
    cell: Cell | undefined;
    row_entry: RowEntry;
    table_state: TableDataState;
}>();

const cell_element = useTemplateRef<HTMLElement>("pretty-title-cell-element");
const caret_element = useTemplateRef<HTMLElement>("target-caret-element");
const current_arrow_data_entry = ref<ArrowDataEntry>();
const level = getNumberOfParent(props.table_state.row_collection, props.row_entry);

const artifact_url = computed((): string => {
    if (dashboard_type === PROJECT_DASHBOARD) {
        return `${props.row_entry.row.artifact_uri}&project-dashboard-id=${dashboard_id}`;
    }

    return `${props.row_entry.row.artifact_uri}&my-dashboard-id=${dashboard_id}`;
});

function doesNotHaveExpandableLinks(): boolean {
    return !hasExpandableLinks(props.row_entry, level);
}

const are_artifact_links_expanded = ref(false);

const caret_class = computed((): string => {
    return (
        "fa-fw pretty-title-caret " +
        (are_artifact_links_expanded.value ? "fa-solid fa-caret-down" : "fa-solid fa-caret-right")
    );
});

const caret_aria_label = computed((): string => {
    return are_artifact_links_expanded.value
        ? $gettext("Hide artifact links")
        : $gettext("Show artifact links");
});

const getCrossRefBadgeClass = (cell: PrettyTitleCell): string =>
    `cross-ref-badge tlp-swatch-${cell.color}`;

function toggleArtifactLinksDisplay(): void {
    are_artifact_links_expanded.value = !are_artifact_links_expanded.value;
    if (are_artifact_links_expanded.value) {
        table_wrapper_operations.expandRow(props.row_entry.row);
        return;
    }

    table_wrapper_operations.collapseRow(props.row_entry.row);
}

const get_parent_element = computed(() => {
    const parent_uuid = props.row_entry.parent_row_uuid;
    if (parent_uuid === null) {
        return undefined;
    }

    return arrow_data_store.getByUUID(parent_uuid);
});

onMounted(() => {
    if (!caret_element.value || !cell_element.value) {
        return;
    }

    current_arrow_data_entry.value = {
        uuid: props.row_entry.row.row_uuid,
        caret: caret_element.value,
        element: cell_element.value,
    };

    arrow_data_store.addEntry(
        props.row_entry.row.row_uuid,
        cell_element.value,
        caret_element.value,
    );
    loadTooltips(cell_element.value);
});

onBeforeUnmount(() => {
    arrow_data_store.removeEntry(props.row_entry.row.row_uuid);
});
</script>

<style scoped lang="scss">
@use "../../../themes/links";
@use "../../../themes/badges";
@use "../../../themes/pretty-title";

.link {
    @include links.link;
}

.cross-ref-badge {
    @include badges.badge;
}

.pretty-title-caret {
    flex-shrink: 0;
    margin: 0 pretty-title.$caret-margin-right 0 0;
}

.caret-button {
    padding: 0;
    transition: color 75ms;
    border: unset;
    background: none;
    color: var(--tlp-dimmed-color);
    cursor: pointer;

    &:hover {
        color: var(--tlp-main-color);
    }

    &[aria-hidden="true"] {
        visibility: hidden;
    }
}
</style>
