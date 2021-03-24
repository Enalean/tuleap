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
    <div class="roadmap-gantt-task-bar" v-bind:class="classes" v-bind:style="style">
        <div class="roadmap-gantt-task-bar-progress"></div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Task } from "../../../type";

@Component
export default class TaskBar extends Vue {
    @Prop({ required: true })
    readonly left!: number;

    @Prop({ required: true })
    readonly width!: number;

    @Prop({ required: true })
    readonly task!: Task;

    get classes(): string[] {
        const classes = ["roadmap-gantt-task-bar-" + this.task.color_name];

        if (this.is_milestone) {
            classes.push("roadmap-gantt-task-bar-milestone");
        }

        return classes;
    }

    get style(): string {
        return this.is_milestone
            ? `left: ${this.left}px;`
            : `left: ${this.left}px; width: ${this.width}px;`;
    }

    get is_milestone(): boolean {
        return (
            !this.task.start ||
            !this.task.end ||
            this.task.end.toISOString() === this.task.start.toISOString()
        );
    }
}
</script>
