<!--
  - Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
    <template v-if="props.cell !== undefined">
        <span
            v-if="props.cell.type === TEXT_CELL"
            class="cell text-cell"
            v-bind:class="getEvenOddClass()"
            data-test="cell"
            v-dompurify-html="props.cell.value"
        ></span>
        <span
            v-if="props.cell.type === TRACKER_CELL"
            class="cell"
            v-bind:class="getEvenOddClass()"
            data-test="cell"
            ><span v-bind:class="getBadgeClass(props.cell)">{{ props.cell.name }}</span></span
        >
        <span
            v-if="props.cell.type === PRETTY_TITLE_CELL"
            class="cell"
            v-bind:class="getEvenOddClass()"
            data-test="cell"
            ><a v-bind:href="props.artifact_uri"
                ><span v-bind:class="getCrossRefBadgeClass(props.cell)"
                    >{{ props.cell.tracker_name }} #{{ props.cell.artifact_id }}</span
                >{{ props.cell.title }}</a
            ></span
        >
        <span
            v-if="
                props.cell.type === DATE_CELL ||
                props.cell.type === NUMERIC_CELL ||
                props.cell.type === PROJECT_CELL
            "
            class="cell"
            v-bind:class="getEvenOddClass()"
            data-test="cell"
            >{{ renderCell(props.cell) }}</span
        >
    </template>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Cell, PrettyTitleCell, TrackerCell } from "../../domain/ArtifactsTable";
import {
    DATE_CELL,
    NUMERIC_CELL,
    PRETTY_TITLE_CELL,
    PROJECT_CELL,
    TEXT_CELL,
    TRACKER_CELL,
} from "../../domain/ArtifactsTable";
import { DATE_FORMATTER, DATE_TIME_FORMATTER } from "../../injection-symbols";

const date_formatter = strictInject(DATE_FORMATTER);
const date_time_formatter = strictInject(DATE_TIME_FORMATTER);

const props = defineProps<{
    cell: Cell | undefined;
    artifact_uri: string;
    even: boolean;
}>();

function renderCell(cell: Cell): string {
    if (cell.type === DATE_CELL) {
        const formatter = cell.with_time ? date_time_formatter : date_formatter;
        return cell.value.mapOr(formatter.format, "");
    }
    if (cell.type === NUMERIC_CELL) {
        return String(cell.value.unwrapOr(""));
    }
    if (cell.type === PROJECT_CELL) {
        return cell.icon !== "" ? cell.icon + " " + cell.name : cell.name;
    }
    return "";
}

const getEvenOddClass = (): string => (props.even ? `even-row` : `odd-row`);

const getBadgeClass = (cell: TrackerCell): string => `tlp-badge-${cell.color}`;

const getCrossRefBadgeClass = (cell: PrettyTitleCell): string =>
    `cross-ref-badge tlp-swatch-${cell.color}`;
</script>

<style scoped lang="scss">
@use "../../../themes/cell";

.cell {
    @include cell.cell-template;

    min-height: var(--tlp-x-large-spacing);
}

.text-cell {
    flex-direction: column;
    justify-content: center;
}

.cross-ref-badge {
    margin: 0 5px 0 0;
}
</style>
