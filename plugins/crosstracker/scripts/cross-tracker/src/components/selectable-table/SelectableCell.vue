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
        <text-cell
            v-if="props.cell.type === TEXT_CELL || props.cell.type === UNKNOWN_CELL"
            class="cell"
            v-bind:text="props.cell.value"
            data-test="cell"
        />
        <span v-if="props.cell.type === USER_CELL" class="cell" data-test="cell"
            ><user-value v-bind:user="props.cell" />
        </span>
        <span v-if="props.cell.type === STATIC_LIST_CELL" class="cell list-cell" data-test="cell">
            <span
                v-for="list_value of props.cell.value"
                v-bind:key="list_value.label"
                v-bind:class="getOptionalBadgeClass(list_value.color)"
                >{{ list_value.label }}</span
            >
        </span>
        <span v-if="props.cell.type === USER_LIST_CELL" class="cell list-cell" data-test="cell">
            <user-value
                v-for="list_value of props.cell.value"
                v-bind:key="list_value.display_name"
                v-bind:user="list_value"
            />
        </span>
        <span
            v-if="props.cell.type === USER_GROUP_LIST_CELL"
            class="cell list-cell"
            data-test="cell"
            ><span
                v-for="list_value of props.cell.value"
                v-bind:key="list_value.label"
                class="user-group"
                >{{ list_value.label }}</span
            ></span
        >
        <span v-if="props.cell.type === TRACKER_CELL" class="cell" data-test="cell"
            ><span v-bind:class="getBadgeClass(props.cell.color)">{{ props.cell.name }}</span></span
        >
        <span v-if="props.cell.type === LINK_TYPE_CELL" class="cell">
            <link-type-cell-component v-bind:cell="props.cell" />
        </span>
        <pretty-title-cell-component
            v-if="props.cell.type === PRETTY_TITLE_CELL"
            v-bind:cell="cell"
            v-bind:artifact_uri="artifact_uri"
            v-bind:expected_number_of_forward_link="expected_number_of_forward_link"
            v-bind:expected_number_of_reverse_link="expected_number_of_reverse_link"
            v-bind:is_last="is_last"
            v-on:toggle-links="toggleArtifactLinksDisplay"
            class="cell"
            v-bind:level="level"
            data-test="cell"
            v-bind:parent_element="parent_element"
            v-bind:parent_caret="parent_caret"
            v-bind:direction="direction"
            v-bind:reverse_links_count="reverse_links_count"
        />

        <span
            v-if="
                props.cell.type === DATE_CELL ||
                props.cell.type === NUMERIC_CELL ||
                props.cell.type === PROJECT_CELL
            "
            class="cell"
            data-test="cell"
            >{{ renderCell(props.cell) }}</span
        >
    </template>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import type { ColorName } from "@tuleap/core-constants";
import type { Option } from "@tuleap/option";
import type { ArtifactLinkDirection, Cell } from "../../domain/ArtifactsTable";
import {
    DATE_CELL,
    LINK_TYPE_CELL,
    NUMERIC_CELL,
    PRETTY_TITLE_CELL,
    PROJECT_CELL,
    STATIC_LIST_CELL,
    TEXT_CELL,
    TRACKER_CELL,
    UNKNOWN_CELL,
    USER_CELL,
    USER_GROUP_LIST_CELL,
    USER_LIST_CELL,
} from "../../domain/ArtifactsTable";
import { DATE_FORMATTER, DATE_TIME_FORMATTER } from "../../injection-symbols";
import UserValue from "./UserValue.vue";
import TextCell from "./TextCell.vue";
import PrettyTitleCellComponent from "./PrettyTitleCellComponent.vue";
import type { ToggleLinks } from "../../helpers/ToggleLinksEmit";
import LinkTypeCellComponent from "./LinkTypeCellComponent.vue";

const date_formatter = strictInject(DATE_FORMATTER);
const date_time_formatter = strictInject(DATE_TIME_FORMATTER);

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

const emit = defineEmits<ToggleLinks>();

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

function toggleArtifactLinksDisplay(parent_element: HTMLElement, parent_caret: HTMLElement): void {
    emit("toggle-links", parent_element, parent_caret);
}

const getBadgeClass = (color: ColorName): string => `tlp-badge-${color} tlp-badge-outline`;

const getOptionalBadgeClass = (option: Option<ColorName>): string =>
    option.mapOr(getBadgeClass, "tlp-badge-secondary tlp-badge-outline");
</script>

<style scoped lang="scss">
@use "../../../themes/cell";

.cell {
    @include cell.cell-template;

    min-height: var(--tlp-x-large-spacing);
}

.list-cell {
    gap: 3px;
    flex-wrap: wrap;
}

.user-group:not(:last-child)::after {
    content: ", ";
}

.cell:last-of-type {
    padding-right: var(--tlp-medium-spacing);
}
</style>
