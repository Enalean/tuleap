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
        class="roadmap-gantt-task-header roadmap-gantt-subtask-header roadmap-gantt-subtask-header-last-one"
        v-bind:class="classes"
    ></div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { EmptySubtasksRow, ErrorRow } from "../../../type";

const props = defineProps<{
    row: ErrorRow | EmptySubtasksRow;
}>();

const classes = computed((): string => {
    return "roadmap-gantt-task-header-" + props.row.for_task.color_name;
});
</script>
