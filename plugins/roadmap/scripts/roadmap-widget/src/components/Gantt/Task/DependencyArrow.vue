<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <svg class="roadmap-gantt-task-dependency" v-bind:style="style">
        <path
            class="roadmap-gantt-task-dependency-line"
            v-bind:class="line_class"
            v-bind:d="path"
            data-test="path"
        />
    </svg>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Task, TaskDimension, TaskDimensionMap } from "../../../type";
import { Styles } from "../../../helpers/styles";
import {
    gap,
    getDownLeftArrow,
    getDownRightArrow,
    getUpLeftArrow,
    getUpRightArrow,
} from "@tuleap/svg-arrow-path";
import { getDimensions } from "../../../helpers/tasks-dimensions";

const props = defineProps<{
    readonly dimensions_map: TaskDimensionMap;
    readonly task: Task;
    readonly dependency: Task;
    readonly percentage: string;
    readonly is_text_displayed_outside_bar: boolean;
    readonly is_error_sign_displayed_outside_bar: boolean;
}>();
const is_task_ends_after_dependency_start = computed((): boolean => {
    const end_of_task = props.task.end || props.task.start;
    const start_of_dependency = props.dependency.start || props.dependency.end;

    if (!end_of_task || !start_of_dependency) {
        return false;
    }

    return end_of_task > start_of_dependency;
});

const task_dimensions = computed((): TaskDimension => {
    return getDimensions(props.task, props.dimensions_map);
});

const dependency_dimensions = computed((): TaskDimension => {
    return getDimensions(props.dependency, props.dimensions_map);
});

const index_task = computed((): number => {
    return task_dimensions.value.index;
});

const index_dependency = computed((): number => {
    return dependency_dimensions.value.index;
});

const right_of_task = computed((): number => {
    return task_dimensions.value.left + task_dimensions.value.width;
});

const right_of_task_and_text = computed((): number => {
    if (props.is_error_sign_displayed_outside_bar) {
        return right_of_task.value + Styles.MINIMUM_WIDTH_TO_DISPLAY_WARNING_SIGN_IN_PX;
    }

    if (props.is_text_displayed_outside_bar && props.percentage.length > 0) {
        const width_of_text =
            Styles.TEXT_PERCENTAGE_APPROXIMATE_WIDTH_OF_PERCENT_SIGN_IN_PX +
            Styles.TEXT_PERCENTAGE_APPROXIMATE_WIDTH_OF_DIGIT_IN_PX *
                (props.percentage.length - 1) +
            2 * Styles.TEXT_PERCENTAGE_MARGIN_IN_PX;

        return right_of_task.value + width_of_text;
    }

    return right_of_task.value;
});

const left_of_dependency = computed((): number => {
    return dependency_dimensions.value.left;
});

const width_without_gap = computed((): number => {
    return Math.abs(left_of_dependency.value - right_of_task_and_text.value);
});
const is_task_and_text_end_after_dependency_start = computed((): boolean => {
    return right_of_task_and_text.value > left_of_dependency.value;
});

const width_with_gap = computed((): number => {
    return width_without_gap.value + 2 * gap;
});
const line_class = computed((): string => {
    return is_task_ends_after_dependency_start.value
        ? "roadmap-gantt-task-dependency-line-ends-after-start"
        : "";
});

const height_without_gap = computed((): number => {
    return Math.abs(index_task.value - index_dependency.value) * Styles.TASK_HEIGHT_IN_PX;
});

const height_with_gap = computed((): number => {
    return height_without_gap.value + 2 * gap;
});

const path = computed((): string => {
    const height = height_with_gap.value;
    const width = width_with_gap.value;

    const is_task_above_dependency = index_task.value < index_dependency.value;

    if (is_task_above_dependency && !is_task_and_text_end_after_dependency_start.value) {
        return getDownRightArrow(width, height, Styles.TASK_HEIGHT_IN_PX);
    }

    if (is_task_above_dependency && is_task_and_text_end_after_dependency_start.value) {
        return getDownLeftArrow(width, height, Styles.TASK_HEIGHT_IN_PX);
    }

    if (!is_task_above_dependency && !is_task_and_text_end_after_dependency_start.value) {
        return getUpRightArrow(width, height, Styles.TASK_HEIGHT_IN_PX);
    }

    return getUpLeftArrow(width, height, Styles.TASK_HEIGHT_IN_PX);
});

const style = computed((): string => {
    let top = Styles.TASK_HEIGHT_IN_PX / 2;
    const left = Math.min(right_of_task_and_text.value, left_of_dependency.value);

    const is_task_above_dependency = index_task.value < index_dependency.value;
    if (!is_task_above_dependency) {
        top -= height_without_gap.value;
    }
    return `left: ${left - gap}px;
            top: ${top - gap}px;
            height: ${height_with_gap.value}px;
            width: ${width_with_gap.value}px`;
});
</script>
