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
    <span v-if="props.cell && props.cell.type === PRETTY_TITLE_CELL" data-test="cell">
        <caret-indentation v-bind:level="level" />
        <button
            type="button"
            v-if="can_display_artifact_link"
            v-on:click="toggleArtifactLinksDisplay()"
            class="caret-button"
            v-bind:aria-hidden="!has_artifact_links"
            v-bind:disabled="!has_artifact_links"
            data-test="pretty-title-links-button"
        >
            <i
                v-bind:class="caret_class"
                role="img"
                v-bind:aria-label="caret_aria_label"
                data-test="pretty-title-caret"
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
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { type Cell, PRETTY_TITLE_CELL, type PrettyTitleCell } from "../../domain/ArtifactsTable";
import { CAN_DISPLAY_ARTIFACT_LINK } from "../../injection-symbols";
import type { ToggleLinks } from "../../helpers/ToggleLinksEmit";
import CaretIndentation from "./CaretIndentation.vue";

const { $gettext } = useGettext();

const can_display_artifact_link = strictInject(CAN_DISPLAY_ARTIFACT_LINK);

const props = defineProps<{
    cell: Cell | undefined;
    artifact_uri: string;
    number_of_forward_link: number;
    number_of_reverse_link: number;
    level: number;
}>();

const emit = defineEmits<ToggleLinks>();

const has_artifact_links = props.number_of_forward_link > 0 || props.number_of_reverse_link > 0;
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
    emit("toggle-links");
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
