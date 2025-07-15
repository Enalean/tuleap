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
        v-if="props.cell && props.cell.type === PRETTY_TITLE_CELL"
        data-test="cell"
        ref="pretty-title-cell-element"
    >
        <artifact-link-arrow
            v-if="
                cell_element &&
                caret_element &&
                parent_element &&
                parent_caret &&
                direction &&
                reverse_links_count !== undefined
            "
            v-bind:child_cell="cell_element"
            v-bind:child_caret="caret_element"
            v-bind:is_last_link="is_last"
            v-bind:parent_cell="parent_element"
            v-bind:parent_caret="parent_caret"
            v-bind:direction="direction"
            v-bind:reverse_links_count="reverse_links_count"
        />
        <caret-indentation v-bind:level="level" />
        <button
            type="button"
            v-on:click="toggleArtifactLinksDisplay()"
            class="caret-button"
            v-bind:aria-hidden="!should_display_links()"
            v-bind:disabled="!should_display_links()"
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
        <a v-bind:href="props.artifact_uri" class="link"
            ><span v-bind:class="getCrossRefBadgeClass(props.cell)"
                >{{ props.cell.tracker_name }} #{{ props.cell.artifact_id }}</span
            >{{ props.cell.title }}</a
        >
    </span>
</template>

<script setup lang="ts">
import { computed, ref, useTemplateRef } from "vue";
import { useGettext } from "vue3-gettext";
import {
    type ArtifactLinkDirection,
    type Cell,
    PRETTY_TITLE_CELL,
    type PrettyTitleCell,
} from "../../domain/ArtifactsTable";
import type { ToggleLinks } from "../../helpers/ToggleLinksEmit";
import CaretIndentation from "./CaretIndentation.vue";
import ArtifactLinkArrow from "./ArtifactLinkArrow.vue";

const { $gettext } = useGettext();

const props = defineProps<{
    cell: Cell | undefined;
    artifact_uri: string;
    expected_number_of_forward_link: number;
    expected_number_of_reverse_link: number;
    level: number;
    is_last: boolean;
    parent_element: HTMLElement | undefined;
    parent_caret: HTMLElement | undefined;
    direction: ArtifactLinkDirection | undefined;
    reverse_links_count: number | undefined;
}>();

const cell_element = useTemplateRef<HTMLElement>("pretty-title-cell-element");
const caret_element = useTemplateRef<HTMLElement>("target-caret-element");

const emit = defineEmits<ToggleLinks>();

function should_display_links(): boolean {
    if (props.level === 0) {
        return props.expected_number_of_forward_link + props.expected_number_of_reverse_link > 0;
    }
    return props.expected_number_of_reverse_link + props.expected_number_of_forward_link > 1;
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
    if (cell_element.value && caret_element.value) {
        emit("toggle-links", cell_element.value, caret_element.value);
    }
}
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
