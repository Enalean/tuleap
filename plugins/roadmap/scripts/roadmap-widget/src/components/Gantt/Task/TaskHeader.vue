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
    <a
        class="roadmap-gantt-task-header"
        v-bind:href="task.html_url"
        v-bind:class="'roadmap-gantt-task-header-' + task.color_name"
    >
        <div
            class="roadmap-gantt-task-header-caret"
            v-if="does_at_least_one_task_have_subtasks"
            data-test="caret-container"
        >
            <i
                class="fas fa-fw fa-caret-right"
                aria-hidden="true"
                v-if="task.has_subtasks"
                data-test="caret"
            ></i>
        </div>
        <div class="roadmap-gantt-task-header-text">
            <span class="roadmap-gantt-task-header-xref">{{ task.xref }}</span>
            <span class="roadmap-gantt-task-header-title">{{ task.title }}</span>
        </div>
    </a>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Task } from "../../../type";
import { namespace } from "vuex-class";

const tasks = namespace("tasks");

@Component
export default class TaskHeader extends Vue {
    @Prop({ required: true })
    readonly task!: Task;

    @tasks.Getter
    readonly does_at_least_one_task_have_subtasks!: boolean;
}
</script>
