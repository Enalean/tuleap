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
    <div class="roadmap-gantt-task-bar-milestone-container" v-bind:style="style">
        <svg
            v-bind:width="width"
            v-bind:height="height"
            v-bind:viewBox="viewbox"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            class="roadmap-gantt-task-bar-milestone"
        >
            <path
                d="M8 3 C11 0 11 0 14 3 L19 8 C22 11 22 11 19 14 L14 19 C11 22 11 22 8 19 L3 14 C0 11 0 11 3 8Z"
                fill="#CBF5EA"
                stroke="#6BE0C5"
                stroke-linejoin="round"
                class="roadmap-gantt-task-bar-milestone-border"
            />
            <path
                d="M8 5 C11 2 11 2 14 5 L17 8 C20 11 20 11 17 14 L14 17 C11 20 11 20 8 17 L5 14 C2 11 2 11 5 8Z"
                fill="#6BE0C5"
                stroke="#6BE0C5"
                stroke-linejoin="round"
                class="roadmap-gantt-task-bar-milestone-progress"
                data-test="progress"
                v-bind:clip-path="clip_path"
                v-if="!is_progress_in_error"
            />
        </svg>
        <i
            class="fas fa-exclamation-triangle roadmap-gantt-task-bar-progress-error-outside-bar"
            aria-hidden="true"
            data-test="progress-error-sign"
            v-if="is_progress_in_error"
        ></i>
        <span
            class="roadmap-gantt-task-bar-progress-text-outside-bar"
            v-else-if="percentage.length > 0"
            data-test="percentage"
        >
            {{ percentage }}
        </span>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Styles } from "../../../helpers/styles";
import type { Task } from "../../../type";

@Component
export default class MilestoneBar extends Vue {
    @Prop({ required: true })
    readonly left!: number;

    @Prop({ required: true })
    readonly task!: Task;

    @Prop({ required: true })
    readonly percentage!: string;

    get width(): number {
        return Styles.MILESTONE_WIDTH_IN_PX;
    }

    get height(): number {
        return this.width;
    }

    get viewbox(): string {
        return `0 0 ${Styles.MILESTONE_WIDTH_IN_PX} ${Styles.MILESTONE_WIDTH_IN_PX}`;
    }

    get style(): string {
        return `left: ${this.left}px;`;
    }

    get clip_path(): string {
        if (this.task.progress === null) {
            return "";
        }

        let clip_x = "23";
        if (this.task.progress <= 0) {
            clip_x = "-1";
        } else if (this.task.progress < 1) {
            clip_x = Math.floor(this.task.progress * 100) + "%";
        }

        return `polygon(-1 -1, ${clip_x} -1, ${clip_x} 23, -1 23)`;
    }

    get is_progress_in_error(): boolean {
        return this.task.progress_error_message.length > 0;
    }
}
</script>
