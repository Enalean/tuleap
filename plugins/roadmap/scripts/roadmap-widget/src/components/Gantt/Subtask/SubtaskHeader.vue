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
        class="roadmap-gantt-task-header roadmap-gantt-subtask-header"
        v-bind:class="classes"
        ref="popover_anchor"
    >
        <header-link
            v-bind:task="row.subtask"
            v-bind:should_display_project="should_display_project"
        />
        <header-invalid-icon v-if="is_task_invalid" data-test="icon" />
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { SubtaskRow } from "../../../type";
import HeaderLink from "../Task/HeaderLink.vue";
import type { Popover } from "@tuleap/tlp-popovers";
import { createPopover } from "@tuleap/tlp-popovers";
import { doesTaskHaveEndDateGreaterOrEqualToStartDate } from "../../../helpers/task-has-valid-dates";
import HeaderInvalidIcon from "../Task/HeaderInvalidIcon.vue";

const props = defineProps<{
    row: SubtaskRow;
    popover_element_id: string;
}>();

const popover = ref<Popover | undefined>();
const popover_anchor = ref<InstanceType<typeof HTMLElement>>();

const is_task_invalid = computed(
    (): boolean => !doesTaskHaveEndDateGreaterOrEqualToStartDate(props.row.subtask),
);

const classes = computed((): string[] => {
    const classes = ["roadmap-gantt-task-header-" + props.row.parent.color_name];

    if (props.row.is_last_one) {
        classes.push("roadmap-gantt-subtask-header-last-one");
    }

    if (is_task_invalid.value) {
        classes.push("roadmap-gantt-subtask-header-for-invalid-task");
    }

    return classes;
});

const should_display_project = computed(
    (): boolean => props.row.parent.project.id !== props.row.subtask.project.id,
);

onMounted(() => {
    const popover_element = document.getElementById(props.popover_element_id);
    if (
        is_task_invalid.value &&
        popover_anchor.value instanceof HTMLElement &&
        popover_element instanceof HTMLElement
    ) {
        popover.value = createPopover(popover_anchor.value, popover_element, {
            placement: "right",
        });
    }
});

onBeforeUnmount(() => {
    if (popover.value) {
        popover.value.destroy();
    }
});
</script>
