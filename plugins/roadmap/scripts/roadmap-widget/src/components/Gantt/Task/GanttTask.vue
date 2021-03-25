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
        <background-grid v-bind:time_units="time_units" />
        <task-bar v-bind:task="task" v-bind:left="left" v-bind:width="width" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Task } from "../../../type";
import TaskHeader from "./TaskHeader.vue";
import BackgroundGrid from "./BackgroundGrid.vue";
import TaskBar from "./TaskBar.vue";
import { getLeftForDate } from "../../../helpers/left-postion";
import { Styles } from "../../../helpers/styles";

@Component({
    components: { TaskBar, BackgroundGrid, TaskHeader },
})
export default class GanttTask extends Vue {
    @Prop({ required: true })
    readonly task!: Task;

    @Prop({ required: true })
    readonly time_units!: Date[];

    get left(): number {
        if (this.task.start) {
            return getLeftForDate(this.task.start, this.time_units);
        }

        if (this.task.end) {
            return getLeftForDate(this.task.end, this.time_units);
        }

        return 0;
    }

    get width(): number {
        if (
            this.task.start &&
            this.task.end &&
            this.task.start.toISOString() !== this.task.end.toISOString()
        ) {
            return Math.max(
                getLeftForDate(this.task.end, this.time_units) - this.left,
                Styles.TASK_BAR_MIN_WIDTH_IN_PX
            );
        }

        return Styles.MILESTONE_WIDTH_IN_PX;
    }
}
</script>
