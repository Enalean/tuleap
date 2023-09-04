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
        <header-invalid-icon v-if="is_task_invalid" data-test="icon" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Task } from "../../../type";
import { namespace } from "vuex-class";
import HeaderLink from "./HeaderLink.vue";
import type { Popover } from "@tuleap/tlp-popovers";
import { createPopover } from "@tuleap/tlp-popovers";
import { doesTaskHaveEndDateGreaterOrEqualToStartDate } from "../../../helpers/task-has-valid-dates";
import HeaderInvalidIcon from "./HeaderInvalidIcon.vue";

const tasks = namespace("tasks");
@Component({
    components: { HeaderInvalidIcon, HeaderLink },
})
export default class TaskHeader extends Vue {
    @Prop({ required: true })
    readonly task!: Task;

    @tasks.Getter
    readonly does_at_least_one_task_have_subtasks!: boolean;

    @tasks.Action
    readonly toggleSubtasks!: (task: Task) => void;

    @Prop({ required: true })
    private readonly popover_element_id!: string;

    private popover: Popover | undefined;

    mounted(): void {
        const popover_element = document.getElementById(this.popover_element_id);
        if (
            this.is_task_invalid &&
            this.$el instanceof HTMLElement &&
            popover_element instanceof HTMLElement
        ) {
            this.popover = createPopover(this.$el, popover_element, {
                placement: "right",
            });
        }
    }

    beforeDestroy(): void {
        if (this.popover) {
            this.popover.destroy();
        }
    }

    get caret_class(): string {
        return this.task.is_expanded ? "fa-caret-down" : "fa-caret-right";
    }

    get classes(): string[] {
        const classes = ["roadmap-gantt-task-header-" + this.task.color_name];

        if (this.task.has_subtasks) {
            classes.push("roadmap-gantt-task-header-with-subtasks");
        }

        if (this.is_task_invalid) {
            classes.push("roadmap-gantt-task-header-for-invalid-task");
        }

        return classes;
    }

    get is_task_invalid(): boolean {
        return !doesTaskHaveEndDateGreaterOrEqualToStartDate(this.task);
    }

    toggle(event: Event): void {
        if (
            event.target instanceof HTMLElement &&
            event.target.closest(
                ".roadmap-gantt-task-header-xref, .roadmap-gantt-task-header-title",
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
