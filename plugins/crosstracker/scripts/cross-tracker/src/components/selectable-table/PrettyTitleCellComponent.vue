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
        <i
            v-if="can_display_artifact_link"
            class="pretty-title-caret"
            v-bind:class="caret_class"
            aria-hidden="true"
            data-test="pretty-title-cell-artifact-link"
        ></i>
        <a v-bind:href="props.artifact_uri" class="link"
            ><span v-bind:class="getCrossRefBadgeClass(props.cell)"
                >{{ props.cell.tracker_name }} #{{ props.cell.artifact_id }}</span
            >{{ props.cell.title }}</a
        >
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { type Cell, PRETTY_TITLE_CELL, type PrettyTitleCell } from "../../domain/ArtifactsTable";
import { CAN_DISPLAY_ARTIFACT_LINK } from "../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";

const can_display_artifact_link = strictInject(CAN_DISPLAY_ARTIFACT_LINK);

const props = defineProps<{
    cell: Cell | undefined;
    artifact_uri: string;
}>();

const caret_class = computed((): string => {
    return "fa-fw fa-solid fa-caret-right";
});

const getCrossRefBadgeClass = (cell: PrettyTitleCell): string =>
    `cross-ref-badge tlp-swatch-${cell.color}`;
</script>

<style scoped lang="scss">
@use "../../../themes/links";
@use "../../../themes/badges";

.link {
    @include links.link;
}

.cross-ref-badge {
    @include badges.badge;
}

.pretty-title-caret {
    flex-shrink: 0;
    margin: 0 5px 0 0;
    color: var(--tlp-dimmed-color);
}
</style>
