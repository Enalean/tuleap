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
        v-if="cell !== undefined && cell.type !== PRETTY_TITLE_CELL"
        class="cell tlp-skeleton-text"
        data-test="empty_cell"
    />
    <span
        v-if="cell !== undefined && cell.type === PRETTY_TITLE_CELL"
        class="cell tlp-skeleton-text"
        data-test="pretty-title-empty_cell"
    >
        <caret-indentation v-bind:level="level" />
        <i
            class="pretty-title-caret tlp-skeleton-icon"
            v-bind:class="caret_class"
            aria-hidden="true"
            data-test="pretty-title-caret"
        ></i>
        <span class="tlp-skeleton-text"></span>
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Cell } from "../../../domain/ArtifactsTable";
import { PRETTY_TITLE_CELL } from "../../../domain/ArtifactsTable";
import CaretIndentation from "../CaretIndentation.vue";

defineProps<{
    cell: Cell | undefined;
    level: number;
}>();

const caret_class = computed((): string => {
    return `fa-fw fa-solid fa-caret-right pretty-title-caret`;
});
</script>

<style scoped lang="scss">
@use "../../../../themes/cell";
@use "../../../../themes/pretty-title";

.cell {
    @include cell.cell-template;

    min-height: var(--tlp-x-large-spacing);
}
</style>
