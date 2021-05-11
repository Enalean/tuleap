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
    <div class="roadmap-gantt-task-header" v-bind:class="classes" v-on:click="toggle">
        <div
            class="roadmap-gantt-task-header-caret"
            v-if="does_at_least_one_task_have_subtasks"
            data-test="caret-container"
        >
            <i
                class="fas fa-fw"
                v-bind:class="caret_class"
                aria-hidden="true"
                v-if="task.has_subtasks"
                data-test="caret"
            ></i>
        </div>
        <header-link v-bind:task="task" v-bind:should_display_project="false" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Task } from "../../../type";
import { namespace } from "vuex-class";
import HeaderLink from "./HeaderLink.vue";

const tasks = namespace("tasks");
@Component({
    components: { HeaderLink },
})
export default class TaskHeader extends Vue {
    @Prop({ required: true })
    readonly task!: Task;

    @tasks.Getter
    readonly does_at_least_one_task_have_subtasks!: boolean;

    @tasks.Action
    readonly toggleSubtasks!: (task: Task) => void;

    get caret_class(): string {
        return this.task.is_expanded ? "fa-caret-down" : "fa-caret-right";
    }

    get classes(): string[] {
        const classes = ["roadmap-gantt-task-header-" + this.task.color_name];

        if (this.task.has_subtasks) {
            classes.push("roadmap-gantt-task-header-with-subtasks");
        }

        return classes;
    }

    toggle(event: Event): void {
        if (
            event.target instanceof HTMLElement &&
            event.target.closest(
                ".roadmap-gantt-task-header-xref, .roadmap-gantt-task-header-title"
            )
        ) {
            return;
        }

        if (this.task.has_subtasks) {
            this.toggleSubtasks(this.task);
        }
    }
}
</script>
