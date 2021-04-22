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
    <div class="roadmap-gantt-task">
        <background-grid
            v-bind:time_period="time_period"
            v-bind:nb_additional_units="nb_additional_units"
        />
        <dependency-arrow
            v-for="dependency of dependencies_to_display"
            v-bind:key="dependency.id"
            v-bind:task="task"
            v-bind:dependency="dependency"
            v-bind:dimensions_map="dimensions_map"
            v-bind:percentage="percentage"
            v-bind:is_text_displayed_outside_bar="is_text_displayed_outside_bar"
        />
        <task-bar
            v-bind:task="task"
            v-bind:left="dimensions.left"
            v-bind:width="dimensions.width"
            v-bind:percentage="percentage"
            v-bind:is_text_displayed_inside_progress_bar="is_text_displayed_inside_progress_bar"
            v-bind:is_text_displayed_outside_progress_bar="is_text_displayed_outside_progress_bar"
            v-bind:is_text_displayed_outside_bar="is_text_displayed_outside_bar"
            ref="bar"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type {
    Task,
    TimePeriod,
    TasksDependencies,
    TaskDimension,
    TaskDimensionMap,
} from "../../../type";
import BackgroundGrid from "./BackgroundGrid.vue";
import TaskBar from "./TaskBar.vue";
import DependencyArrow from "./DependencyArrow.vue";
import { getDimensions } from "../../../helpers/tasks-dimensions";
import { createPopover } from "@tuleap/tlp-popovers";
import type { Popover } from "@tuleap/tlp-popovers/types/scripts/lib/tlp-popovers/src/popovers";
import BarPopover from "./BarPopover.vue";
import { Styles } from "../../../helpers/styles";

@Component({
    components: { BarPopover, DependencyArrow, TaskBar, BackgroundGrid },
})
export default class GanttTask extends Vue {
    $refs!: {
        bar: TaskBar;
        popover: BarPopover;
    };

    @Prop({ required: true })
    readonly task!: Task;

    @Prop({ required: true })
    readonly dimensions_map!: TaskDimensionMap;

    @Prop({ required: true })
    readonly time_period!: TimePeriod;

    @Prop({ required: true })
    readonly nb_additional_units!: number;

    @Prop({ required: true })
    readonly dependencies!: TasksDependencies;

    @Prop({ required: true })
    readonly dependencies_nature_to_display!: string | null;

    @Prop({ required: true })
    private readonly popover_element_id!: string;

    private popover: Popover | undefined;

    mounted(): void {
        const popover_element = document.getElementById(this.popover_element_id);
        if (this.$refs.bar.$el instanceof HTMLElement && popover_element instanceof HTMLElement) {
            this.popover = createPopover(this.$refs.bar.$el, popover_element, {
                placement: "right-start",
            });
        }
    }

    beforeDestroy(): void {
        if (this.popover) {
            this.popover.destroy();
        }
    }

    get dimensions(): TaskDimension {
        return getDimensions(this.task, this.dimensions_map);
    }

    get dependencies_to_display(): Task[] {
        if (this.dependencies_nature_to_display === null) {
            return [];
        }

        const dependencies_for_current_task = this.dependencies.get(this.task);
        if (!dependencies_for_current_task) {
            return [];
        }

        return dependencies_for_current_task.get(this.dependencies_nature_to_display) || [];
    }

    get percentage(): string {
        if (this.task.progress === null) {
            return "";
        }

        return Math.round(this.task.progress * 100) + "%";
    }

    get normalized_progress(): number {
        return Math.max(0, Math.min(1, this.task.progress || 0));
    }

    get space_inside_progress_bar_in_px(): number {
        return this.dimensions.width * this.normalized_progress;
    }

    get remaining_space_at_the_right_of_the_progress_bar_in_px(): number {
        return this.dimensions.width * (1 - this.normalized_progress);
    }

    get is_text_displayed_inside_progress_bar(): boolean {
        if (this.task.progress === null) {
            return false;
        }

        if (this.is_text_displayed_outside_progress_bar) {
            return false;
        }

        return this.does_text_fit_in_space_inside_progress;
    }

    get is_text_displayed_outside_progress_bar(): boolean {
        if (this.task.progress === null) {
            return false;
        }

        return this.does_text_fit_in_remaining_space_at_the_right_of_the_progress_bar;
    }

    get is_text_displayed_outside_bar(): boolean {
        if (this.task.progress === null) {
            return false;
        }

        return (
            !this.does_text_fit_in_space_inside_progress &&
            !this.is_text_displayed_outside_progress_bar
        );
    }

    get does_text_fit_in_space_inside_progress(): boolean {
        return this.doesTextFitsIn(this.space_inside_progress_bar_in_px);
    }

    get does_text_fit_in_remaining_space_at_the_right_of_the_progress_bar(): boolean {
        return this.doesTextFitsIn(this.remaining_space_at_the_right_of_the_progress_bar_in_px);
    }

    doesTextFitsIn(width: number): boolean {
        return width > Styles.TEXT_PERCENTAGE_IN_PROGRESS_BAR_THRESOLD_IN_PX;
    }
}
</script>
