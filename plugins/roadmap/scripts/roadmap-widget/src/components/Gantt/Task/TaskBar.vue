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
    <task-bar-milestone
        v-if="task.is_milestone"
        v-bind:task="task"
        v-bind:left="left"
        v-bind:popover_element_id="popover_element_id"
        v-bind:class="container_classes"
    />
    <task-bar-progress
        v-else
        v-bind:left="left"
        v-bind:width="width"
        v-bind:task="task"
        v-bind:percentage="percentage"
        v-bind:is_text_displayed_inside_progress_bar="is_text_displayed_inside_progress_bar"
        v-bind:is_text_displayed_outside_progress_bar="is_text_displayed_outside_progress_bar"
        v-bind:is_text_displayed_outside_bar="is_text_displayed_outside_bar"
        v-bind:is_error_sign_displayed_outside_bar="is_error_sign_displayed_outside_bar"
        v-bind:is_error_sign_displayed_inside_bar="is_error_sign_displayed_inside_bar"
        v-bind:popover_element_id="popover_element_id"
        v-bind:container_classes="container_classes"
    />
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Task } from "../../../type";
import TaskBarMilestone from "./TaskBarMilestone.vue";
import TaskBarProgress from "./TaskBarProgress.vue";

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
    popover_element_id: string;
}>();

const container_classes = computed(
    () => "roadmap-gantt-task-bar-container-" + props.task.color_name,
);
</script>
