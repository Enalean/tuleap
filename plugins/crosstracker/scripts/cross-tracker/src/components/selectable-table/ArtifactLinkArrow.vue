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
    <svg class="arrow" v-bind:style="svg_styling">
        <path class="path" v-bind:d="path" />
    </svg>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { EMITTER } from "../../injection-symbols";
import { SELECTABLE_TABLE_RESIZED_EVENT } from "../../helpers/widget-events";
import type { ArtifactLinkDirection } from "../../domain/ArtifactsTable";
import { REVERSE_DIRECTION } from "../../domain/ArtifactsTable";

interface Point {
    x: number;
    y: number;
}

interface ArrowPoints {
    parent_point: Point;
    child_point: Point;
}

const emitter = strictInject(EMITTER);

const FORWARD_LINKS_OFFSET = 10;
const OFFSET_TO_AVOID_CUT_ARROW = 1;
const ARROWHEAD_STEEPNESS = 4;

const props = defineProps<{
    child_cell: HTMLElement;
    child_caret: HTMLElement;
    parent_cell: HTMLElement;
    parent_caret: HTMLElement;
    is_last_link: boolean;
    direction: ArtifactLinkDirection;
    reverse_links_count: number;
}>();

const svg_styling = ref<string>(getSVGStyle());
const arrow_points = ref<ArrowPoints>(
    getArrowPoints(props.parent_cell, props.parent_caret, props.child_caret),
);

const path = computed((): string =>
    props.direction === REVERSE_DIRECTION
        ? getReversePath()
        : props.reverse_links_count > 0
          ? getForwardPath(FORWARD_LINKS_OFFSET)
          : getForwardPath(0),
);

function getSVGStyle(): string {
    const child_cell_offsetBottom = props.child_cell.offsetTop + props.child_cell.offsetHeight;
    const parent_cell_offsetBottom = props.parent_cell.offsetTop + props.parent_cell.offsetHeight;

    return `position: absolute;
           left: ${props.parent_caret.offsetLeft}px;
           width: ${props.child_caret.offsetLeft - props.parent_caret.offsetLeft}px;
           top: ${parent_cell_offsetBottom}px;
           height: ${child_cell_offsetBottom - parent_cell_offsetBottom}px;`;
}

function getArrowPoints(
    parent_cell: HTMLElement,
    parent_caret: HTMLElement,
    child_caret: HTMLElement,
): ArrowPoints {
    return {
        parent_point: {
            x: parent_caret.offsetWidth / 2,
            y: 0 + OFFSET_TO_AVOID_CUT_ARROW,
        },
        child_point: {
            x: child_caret.offsetLeft - parent_caret.offsetLeft - OFFSET_TO_AVOID_CUT_ARROW,
            y:
                child_caret.offsetTop +
                child_caret.offsetHeight / 2 -
                (parent_cell.offsetTop + parent_cell.offsetHeight),
        },
    };
}

function resetProps(): void {
    arrow_points.value = getArrowPoints(props.parent_cell, props.parent_caret, props.child_caret);
    svg_styling.value = getSVGStyle();
}

function drawLine(start_x: number, end_x: number, start_y: number, end_y: number): string {
    return `M${start_x} ${start_y} L${end_x} ${end_y}`;
}

function drawHorizontalArrow(start_x: number, end_x: number, y: number): string {
    return (
        drawLine(start_x, end_x, y, y) +
        `M${end_x - ARROWHEAD_STEEPNESS} ${y + ARROWHEAD_STEEPNESS}
        L${end_x} ${y}
        M${end_x - ARROWHEAD_STEEPNESS} ${y - ARROWHEAD_STEEPNESS}
        L${end_x} ${y}`
    );
}

function drawVerticalArrow(start_y: number, end_y: number, x: number): string {
    return (
        drawLine(x, x, start_y, end_y) +
        `M${x} ${end_y}
        L${x - ARROWHEAD_STEEPNESS} ${end_y + ARROWHEAD_STEEPNESS}
        M${x} ${end_y}
        L${x + ARROWHEAD_STEEPNESS} ${end_y + ARROWHEAD_STEEPNESS}`
    );
}

function getForwardPath(path_offset: number): string {
    const parent_point_x_with_offset = arrow_points.value.parent_point.x + path_offset;

    let path = drawHorizontalArrow(
        parent_point_x_with_offset,
        arrow_points.value.child_point.x,
        arrow_points.value.child_point.y,
    );

    if (props.is_last_link) {
        path += drawLine(
            parent_point_x_with_offset,
            parent_point_x_with_offset,
            arrow_points.value.parent_point.y,
            arrow_points.value.child_point.y,
        );
    }

    return path;
}

function getReversePath(): string {
    let path = drawLine(
        arrow_points.value.parent_point.x,
        arrow_points.value.child_point.x,
        arrow_points.value.child_point.y,
        arrow_points.value.child_point.y,
    );

    if (props.is_last_link) {
        path += drawVerticalArrow(
            arrow_points.value.child_point.y,
            arrow_points.value.parent_point.y,
            arrow_points.value.parent_point.x,
        );
    }

    return path;
}

onMounted(() => {
    emitter.on(SELECTABLE_TABLE_RESIZED_EVENT, resetProps);
});

onBeforeUnmount(() => {
    emitter.off(SELECTABLE_TABLE_RESIZED_EVENT, resetProps);
});
</script>

<style scoped lang="scss">
.path {
    stroke-width: 1.25;
    stroke-linecap: round;
    stroke: var(--tlp-dimmed-color);
    fill: none;
}
</style>
