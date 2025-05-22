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
        v-if="task.is_milestone"
        v-bind:task="task"
        v-bind:left="left"
        v-bind:class="container_classes"
    />
    <div
        class="roadmap-gantt-task-bar-container"
        v-bind:class="container_classes"
        v-bind:style="style_container"
        data-test="container"
        v-else
    >
        <div
            class="roadmap-gantt-task-bar"
            v-bind:style="style_bar"
            data-test="bar"
            v-bind:class="bar_classes"
        >
            <i
                class="fas fa-exclamation-triangle"
                aria-hidden="true"
                data-test="progress-error-sign"
                v-if="is_error_sign_displayed_inside_bar"
            ></i>
            <template v-else-if="!is_progress_in_error">
                <div
                    class="roadmap-gantt-task-bar-progress"
                    data-test="progress"
                    v-bind:style="progress_style"
                >
                    <span
                        class="roadmap-gantt-task-bar-progress-text-inside-progress-bar"
                        v-if="is_text_displayed_inside_progress_bar"
                        data-test="percentage"
                    >
                        {{ percentage }}
                    </span>
                </div>
                <span
                    class="roadmap-gantt-task-bar-progress-text-outside-progress-bar"
                    v-if="is_text_displayed_outside_progress_bar"
                    data-test="percentage"
                >
                    {{ percentage }}
                </span>
            </template>
        </div>
        <i
            class="fas fa-exclamation-triangle roadmap-gantt-task-bar-progress-error-outside-bar"
            aria-hidden="true"
            data-test="progress-error-sign"
            v-if="is_error_sign_displayed_outside_bar"
        ></i>
        <span
            class="roadmap-gantt-task-bar-progress-text-outside-bar"
            v-else-if="!is_progress_in_error && is_text_displayed_outside_bar"
            data-test="percentage"
        >
            {{ percentage }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Task } from "../../../type";
import MilestoneBar from "./MilestoneBar.vue";

const props = defineProps<{
    left: number;
    width: number;
    task: Task;
    percentage: string;
    is_text_displayed_inside_progress_bar: boolean;
    is_text_displayed_outside_progress_bar: boolean;
    is_text_displayed_outside_bar: boolean;
    is_error_sign_displayed_outside_bar: boolean;
    is_error_sign_displayed_inside_bar: boolean;
}>();

const container_classes = computed(
    () => "roadmap-gantt-task-bar-container-" + props.task.color_name,
);
const is_progress_in_error = computed(() => props.task.progress_error_message.length > 0);
const style_container = computed(() => `left: ${props.left}px;`);
const style_bar = computed(() => `width: ${props.width}px;`);

const bar_classes = computed(() => {
    const classes = [];

    if (is_progress_in_error.value) {
        classes.push("roadmap-gantt-task-bar-with-progress-in-error");
    }

    if (props.task.are_dates_implied) {
        classes.push("roadmap-gantt-task-bar-with-dates-implied");
    }

    return classes;
});

const progress_style = computed(() => {
    if (props.task.progress === null) {
        return "";
    }

    const width_in_percent = Math.max(0, Math.min(100, props.task.progress * 100));

    return `width: ${width_in_percent}%;`;
});
</script>
