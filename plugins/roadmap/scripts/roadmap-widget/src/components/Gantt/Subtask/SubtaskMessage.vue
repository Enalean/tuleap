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
    <div v-bind:class="message_class" v-bind:style="style">
        <i
            class="fas roadmap-gantt-subtask-header-message-icon"
            v-bind:class="icon"
            aria-hidden="true"
        ></i>
        {{
            isErrorRow(row)
                ? $gettext("An error occurred while retrieving the children.")
                : $gettext("Actually there isn't any children you can see here.")
        }}
        <button
            type="button"
            class="tlp-button-primary tlp-button-mini roadmap-gantt-subtask-header-message-button"
            v-if="should_button_be_displayed"
            v-on:click="userUndestandsThatThereAreNoSubtasksToBeDisplayed"
            data-test="button"
        >
            <i class="fas fa-check tlp-button-icon" aria-hidden="true"></i>
            <span>{{ $gettext("Ok, got it") }}</span>
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import { Styles } from "../../../helpers/styles";
import type {
    EmptySubtasksRow,
    ErrorRow,
    Row,
    TaskDimension,
    TaskDimensionMap,
} from "../../../type";
import { getDimensions } from "../../../helpers/tasks-dimensions";
import { useNamespacedMutations } from "vuex-composition-helpers";

onMounted((): void => {});

const props = defineProps<{
    row: ErrorRow | EmptySubtasksRow;
    dimensions_map: TaskDimensionMap;
    nb_iterations_ribbons: number;
}>();

const { removeSubtasksDisplayForTask } = useNamespacedMutations("tasks", [
    "removeSubtasksDisplayForTask",
]);

const message_class = computed((): string => {
    return isErrorRow(props.row)
        ? "roadmap-gantt-subtask-header-error-message"
        : "roadmap-gantt-subtask-header-info-message";
});

const dimensions = computed(
    (): TaskDimension => getDimensions(props.row.for_task, props.dimensions_map),
);

const style = computed((): string => {
    const top =
        (dimensions.value.index + 1) * Styles.TASK_HEIGHT_IN_PX +
        2 * Styles.TIME_UNIT_HEIGHT_IN_PX +
        Styles.TODAY_PIN_HEAD_SIZE_IN_PX +
        props.nb_iterations_ribbons * Styles.ITERATION_HEIGHT_IN_PX +
        1;

    return `top: ${top}px`;
});

const should_button_be_displayed = computed((): boolean => !isErrorRow(props.row));

const icon = computed((): string => {
    return isErrorRow(props.row) ? "fa-exclamation-circle" : "fa-info-circle";
});

function userUndestandsThatThereAreNoSubtasksToBeDisplayed(): void {
    removeSubtasksDisplayForTask(props.row.for_task);
}

function isErrorRow(row: Row): row is ErrorRow {
    return "is_error" in row && row.is_error;
}
</script>
