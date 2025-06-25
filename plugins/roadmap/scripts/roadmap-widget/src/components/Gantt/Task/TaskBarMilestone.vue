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
    <div
        class="roadmap-gantt-task-bar-milestone-container"
        v-bind:style="style"
        ref="container_ref"
    >
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
                v-bind:class="border_classes"
            />
            <path
                d="M8 5 C11 2 11 2 14 5 L17 8 C20 11 20 11 17 14 L14 17 C11 20 11 20 8 17 L5 14 C2 11 2 11 5 8Z"
                fill="#6BE0C5"
                stroke="#6BE0C5"
                stroke-linejoin="round"
                class="roadmap-gantt-task-bar-milestone-progress"
                data-test="progress"
            />
        </svg>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, toRef } from "vue";
import { Styles } from "../../../helpers/styles";
import type { Task } from "../../../type";
import { usePopover } from "../../../helpers/create-and-dispose-popover";

const props = defineProps<{
    left: number;
    task: Task;
    popover_element_id: string;
}>();

const container_ref = ref<HTMLElement>();
const popover_element_id_ref = toRef(props, "popover_element_id");

const width = computed((): number => {
    return Styles.MILESTONE_WIDTH_IN_PX;
});

const height = computed((): number => {
    return width.value;
});

const viewbox = computed((): string => {
    return `0 0 ${Styles.MILESTONE_WIDTH_IN_PX} ${Styles.MILESTONE_WIDTH_IN_PX}`;
});

const style = computed((): string => {
    return `left: ${props.left}px;`;
});

const border_classes = computed((): string => {
    return props.task.are_dates_implied
        ? "roadmap-gantt-task-bar-milestone-border-with-dates-implied"
        : "";
});

usePopover(container_ref, popover_element_id_ref);
</script>
