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
    <div class="roadmap-gantt-task-header" v-bind:class="classes" v-on:click="toggle" ref="element">
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

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from "vue";
import { useNamespacedGetters, useNamespacedActions } from "vuex-composition-helpers";
import type { Popover } from "@tuleap/tlp-popovers";
import { createPopover } from "@tuleap/tlp-popovers";
import type { Task } from "../../../type";
import { doesTaskHaveEndDateGreaterOrEqualToStartDate } from "../../../helpers/task-has-valid-dates";
import HeaderLink from "./HeaderLink.vue";
import HeaderInvalidIcon from "./HeaderInvalidIcon.vue";

const props = defineProps<{
    task: Task;
    popover_element_id: string;
}>();

const popover = ref<Popover | undefined>();
const element = ref<HTMLElement>();

const { does_at_least_one_task_have_subtasks } = useNamespacedGetters("tasks", [
    "does_at_least_one_task_have_subtasks",
]);
const { toggleSubtasks } = useNamespacedActions("tasks", ["toggleSubtasks"]);

const is_task_invalid = computed(() => !doesTaskHaveEndDateGreaterOrEqualToStartDate(props.task));

onMounted(() => {
    const popover_element = document.getElementById(props.popover_element_id);
    if (
        is_task_invalid.value &&
        element.value instanceof HTMLElement &&
        popover_element instanceof HTMLElement
    ) {
        popover.value = createPopover(element.value, popover_element, {
            placement: "right",
        });
    }
});
onBeforeUnmount(() => {
    popover.value?.destroy();
});

const caret_class = computed(() => (props.task.is_expanded ? "fa-caret-down" : "fa-caret-right"));

const classes = computed(() => {
    const classes = ["roadmap-gantt-task-header-" + props.task.color_name];

    if (props.task.has_subtasks) {
        classes.push("roadmap-gantt-task-header-with-subtasks");
    }

    if (is_task_invalid.value) {
        classes.push("roadmap-gantt-task-header-for-invalid-task");
    }

    return classes;
});

function toggle(event: Event): void {
    if (
        event.target instanceof HTMLElement &&
        event.target.closest(".roadmap-gantt-task-header-xref, .roadmap-gantt-task-header-title")
    ) {
        return;
    }

    if (props.task.has_subtasks) {
        toggleSubtasks(props.task);
    }
}
</script>
