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
    <div class="roadmap-gantt-task">
        <background-grid
            v-bind:time_period="time_period"
            v-bind:nb_additional_units="nb_additional_units"
        />
        <template v-if="is_task_valid">
            <dependency-arrow
                v-for="dependency of dependencies_to_display"
                v-bind:key="getDependencyKey(dependency)"
                v-bind:task="task"
                v-bind:dependency="dependency"
                v-bind:dimensions_map="dimensions_map"
                v-bind:percentage="percentage"
                v-bind:is_text_displayed_outside_bar="is_text_displayed_outside_bar"
                v-bind:is_error_sign_displayed_outside_bar="is_error_sign_displayed_outside_bar"
            />
            <task-bar
                v-bind:task="task"
                v-bind:left="dimensions.left"
                v-bind:width="dimensions.width"
                v-bind:percentage="percentage"
                v-bind:is_text_displayed_inside_progress_bar="is_text_displayed_inside_progress_bar"
                v-bind:is_text_displayed_outside_progress_bar="
                    is_text_displayed_outside_progress_bar
                "
                v-bind:is_text_displayed_outside_bar="is_text_displayed_outside_bar"
                v-bind:is_error_sign_displayed_inside_bar="is_error_sign_displayed_inside_bar"
                v-bind:is_error_sign_displayed_outside_bar="is_error_sign_displayed_outside_bar"
                v-bind:popover_element_id="popover_element_id"
                ref="bar"
            />
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useNamespacedGetters, useState } from "vuex-composition-helpers";
import type { Task, TaskDimensionMap, TasksDependencies } from "../../../type";
import { Styles } from "../../../helpers/styles";
import { doesTaskHaveEndDateGreaterOrEqualToStartDate } from "../../../helpers/task-has-valid-dates";
import { getDimensions } from "../../../helpers/tasks-dimensions";
import TaskBar from "./TaskBar.vue";
import DependencyArrow from "./DependencyArrow.vue";
import BackgroundGrid from "./BackgroundGrid.vue";

const { time_period } = useNamespacedGetters("timeperiod", ["time_period"]);
const { show_closed_elements } = useState(["show_closed_elements"]);

const props = defineProps<{
    task: Task;
    dimensions_map: TaskDimensionMap;
    nb_additional_units: number;
    dependencies: TasksDependencies;
    dependencies_nature_to_display?: string | null;
    popover_element_id: string;
}>();

const doesTextFitsIn = (width: number) =>
    width > Styles.TEXT_PERCENTAGE_IN_PROGRESS_BAR_THRESOLD_IN_PX;

const is_task_valid = computed(() => doesTaskHaveEndDateGreaterOrEqualToStartDate(props.task));

function getDependencyKey(dependency: Task): string {
    return (
        "dependency-" + dependency.id + (dependency.parent ? "-parent-" + dependency.parent.id : "")
    );
}

const dimensions = computed(() => getDimensions(props.task, props.dimensions_map));

const dependencies_to_display = computed(() => {
    if (
        props.dependencies_nature_to_display === null ||
        props.dependencies_nature_to_display === undefined
    ) {
        return [];
    }

    const dependencies_for_current_task = props.dependencies.get(props.task);
    if (!dependencies_for_current_task) {
        return [];
    }
    let dependencies_to_filter = dependencies_for_current_task.get(
        props.dependencies_nature_to_display,
    );

    let dependencies_to_display: Task[] = [];
    if (!dependencies_to_filter) {
        return [];
    }
    dependencies_to_filter.forEach((dependency) => {
        if (dependency.is_open || show_closed_elements.value) {
            dependencies_to_display.push(dependency);
        }
    });

    return dependencies_to_display;
});

const percentage = computed(() => {
    if (props.task.progress === null) {
        return "";
    }

    return Math.round(props.task.progress * 100) + "%";
});

const normalized_progress = computed(() => Math.max(0, Math.min(1, props.task.progress || 0)));

const space_inside_progress_bar_in_px = computed(
    () => dimensions.value.width * normalized_progress.value,
);

const remaining_space_at_the_right_of_the_progress_bar_in_px = computed(
    () => dimensions.value.width * (1 - normalized_progress.value),
);

const does_text_fit_in_remaining_space_at_the_right_of_the_progress_bar = computed(() =>
    doesTextFitsIn(remaining_space_at_the_right_of_the_progress_bar_in_px.value),
);

const is_text_displayed_outside_progress_bar = computed(() => {
    if (props.task.is_milestone) {
        return false;
    }

    if (props.task.progress === null) {
        return false;
    }

    return does_text_fit_in_remaining_space_at_the_right_of_the_progress_bar.value;
});

const does_text_fit_in_space_inside_progress = computed(() =>
    doesTextFitsIn(space_inside_progress_bar_in_px.value),
);

const is_text_displayed_inside_progress_bar = computed(() => {
    if (props.task.is_milestone) {
        return false;
    }

    if (props.task.progress === null) {
        return false;
    }

    if (is_text_displayed_outside_progress_bar.value) {
        return false;
    }

    return does_text_fit_in_space_inside_progress.value;
});

const is_text_displayed_outside_bar = computed(() => {
    if (props.task.is_milestone) {
        return false;
    }

    if (props.task.progress === null) {
        return false;
    }

    return (
        !does_text_fit_in_space_inside_progress.value &&
        !is_text_displayed_outside_progress_bar.value
    );
});

const is_progress_in_error = computed(() => props.task.progress_error_message.length > 0);

const is_error_sign_displayed_outside_bar = computed(() => {
    if (props.task.is_milestone) {
        return false;
    }

    return (
        is_progress_in_error.value &&
        dimensions.value.width < Styles.MINIMUM_WIDTH_TO_DISPLAY_WARNING_SIGN_IN_PX
    );
});

const is_error_sign_displayed_inside_bar = computed(() => {
    if (props.task.is_milestone) {
        return false;
    }

    return (
        is_progress_in_error.value &&
        dimensions.value.width >= Styles.MINIMUM_WIDTH_TO_DISPLAY_WARNING_SIGN_IN_PX
    );
});
</script>
