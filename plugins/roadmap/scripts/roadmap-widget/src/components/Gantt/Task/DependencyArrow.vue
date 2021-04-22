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
        />
    </svg>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Task, TaskDimension, TaskDimensionMap } from "../../../type";
import { Styles } from "../../../helpers/styles";
import { gap } from "../../../helpers/path";
import {
    getDownLeftArrow,
    getDownRightArrow,
    getUpLeftArrow,
    getUpRightArrow,
} from "../../../helpers/svg-arrow-path";
import { getDimensions } from "../../../helpers/tasks-dimensions";

@Component
export default class DependencyArrow extends Vue {
    @Prop({ required: true })
    readonly dimensions_map!: TaskDimensionMap;

    @Prop({ required: true })
    readonly task!: Task;

    @Prop({ required: true })
    readonly dependency!: Task;

    @Prop({ required: true })
    readonly percentage!: string;

    @Prop({ required: true })
    readonly is_text_displayed_outside_bar!: boolean;

    get style(): string {
        let top = Styles.TASK_HEIGHT_IN_PX / 2;
        const left = Math.min(this.right_of_task, this.left_of_dependency);

        const is_task_above_dependency = this.index_task < this.index_dependency;
        if (!is_task_above_dependency) {
            top -= this.height_without_gap;
        }
        return `left: ${left - gap}px;
            top: ${top - gap}px;
            height: ${this.height_with_gap}px;
            width: ${this.width_with_gap}px`;
    }

    get path(): string {
        const height = this.height_with_gap;
        const width = this.width_with_gap;

        const is_task_above_dependency = this.index_task < this.index_dependency;

        if (is_task_above_dependency && !this.is_task_ends_after_dependency_start) {
            return getDownRightArrow(width, height);
        }

        if (is_task_above_dependency && this.is_task_ends_after_dependency_start) {
            return getDownLeftArrow(width, height);
        }

        if (!is_task_above_dependency && !this.is_task_ends_after_dependency_start) {
            return getUpRightArrow(width, height);
        }

        return getUpLeftArrow(width, height);
    }

    get line_class(): string {
        return this.is_task_ends_after_dependency_start
            ? "roadmap-gantt-task-dependency-line-ends-after-start"
            : "";
    }

    get is_task_ends_after_dependency_start(): boolean {
        return this.right_of_task > this.left_of_dependency;
    }

    get task_dimensions(): TaskDimension {
        return getDimensions(this.task, this.dimensions_map);
    }

    get dependency_dimensions(): TaskDimension {
        return getDimensions(this.dependency, this.dimensions_map);
    }

    get index_task(): number {
        return this.task_dimensions.index;
    }

    get index_dependency(): number {
        return this.dependency_dimensions.index;
    }

    get width_without_gap(): number {
        return Math.abs(this.left_of_dependency - this.right_of_task);
    }

    get right_of_task(): number {
        const right_of_bar = this.task_dimensions.left + this.task_dimensions.width;
        if (this.is_text_displayed_outside_bar && this.percentage.length > 0) {
            const width_of_text =
                Styles.TEXT_PERCENTAGE_APPROXIMATE_WIDTH_OF_PERCENT_SIGN_IN_PX +
                Styles.TEXT_PERCENTAGE_APPROXIMATE_WIDTH_OF_DIGIT_IN_PX *
                    (this.percentage.length - 1) +
                2 * Styles.TEXT_PERCENTAGE_MARGIN_IN_PX;

            return right_of_bar + width_of_text;
        }

        return right_of_bar;
    }

    get left_of_dependency(): number {
        return this.dependency_dimensions.left;
    }

    get width_with_gap(): number {
        return this.width_without_gap + 2 * gap;
    }

    get height_without_gap(): number {
        return Math.abs(this.index_task - this.index_dependency) * Styles.TASK_HEIGHT_IN_PX;
    }

    get height_with_gap(): number {
        return this.height_without_gap + 2 * gap;
    }
}
</script>
