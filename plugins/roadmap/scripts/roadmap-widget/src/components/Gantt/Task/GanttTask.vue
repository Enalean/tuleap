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
        <task-header v-bind:task="task" />
        <background-grid
            v-bind:time_period="time_period"
            v-bind:nb_additional_units="nb_additional_units"
        />
        <dependency-arrow
            v-for="dependency of dependencies_to_display"
            v-bind:key="dependency.id"
            v-bind:time_period="time_period"
            v-bind:task="task"
            v-bind:dependency="dependency"
            v-bind:tasks="tasks"
        />
        <task-bar
            v-bind:task="task"
            v-bind:left="dimensions.left"
            v-bind:width="dimensions.width"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Task, TimePeriod, TasksDependencies, TaskDimension } from "../../../type";
import TaskHeader from "./TaskHeader.vue";
import BackgroundGrid from "./BackgroundGrid.vue";
import TaskBar from "./TaskBar.vue";
import { getDimensions } from "../../../helpers/tasks-dimensions";
import DependencyArrow from "./DependencyArrow.vue";

@Component({
    components: { DependencyArrow, TaskBar, BackgroundGrid, TaskHeader },
})
export default class GanttTask extends Vue {
    @Prop({ required: true })
    readonly task!: Task;

    @Prop({ required: true })
    readonly tasks!: Task[];

    @Prop({ required: true })
    readonly time_period!: TimePeriod;

    @Prop({ required: true })
    readonly nb_additional_units!: number;

    @Prop({ required: true })
    readonly dependencies!: TasksDependencies;

    get dimensions(): TaskDimension {
        return getDimensions(this.task, this.time_period);
    }

    get dependencies_to_display(): Task[] {
        const dependencies_for_current_task = this.dependencies.get(this.task);
        if (!dependencies_for_current_task) {
            return [];
        }

        let flattened_deps: Task[] = [];
        dependencies_for_current_task.forEach((dependencies_for_a_nature) => {
            flattened_deps = [...flattened_deps, ...dependencies_for_a_nature];
        });

        return flattened_deps;
    }
}
</script>
