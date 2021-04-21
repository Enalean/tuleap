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
    <milestone-bar
        v-if="is_milestone"
        v-bind:task="task"
        v-bind:left="left"
        v-bind:class="classes"
    />
    <div class="roadmap-gantt-task-bar" v-bind:class="classes" v-bind:style="style" v-else>
        <div
            class="roadmap-gantt-task-bar-progress"
            data-test="progress"
            v-bind:style="progress_style"
        ></div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Task } from "../../../type";
import MilestoneBar from "./MilestoneBar.vue";
@Component({
    components: { MilestoneBar },
})
export default class TaskBar extends Vue {
    @Prop({ required: true })
    readonly left!: number;

    @Prop({ required: true })
    readonly width!: number;

    @Prop({ required: true })
    readonly task!: Task;

    get classes(): string[] {
        const classes = ["roadmap-gantt-task-bar-" + this.task.color_name];

        return classes;
    }

    get style(): string {
        return `left: ${this.left}px; width: ${this.width}px;`;
    }

    get is_milestone(): boolean {
        return (
            !this.task.start ||
            !this.task.end ||
            this.task.end.toISOString() === this.task.start.toISOString()
        );
    }

    get progress_style(): string {
        if (this.task.progress === null) {
            return "";
        }

        const width_in_percent = Math.max(0, Math.min(100, this.task.progress * 100));

        return `width: ${width_in_percent}%;`;
    }
}
</script>
