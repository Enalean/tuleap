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
    <div>
        <div class="roadmap-gantt-controls">
            <time-period-control v-bind:value="timescale" v-on:input="setTimescale($event)" />
            <dependency-nature-control
                v-bind:value="dependencies_nature_to_display"
                v-on:input="updateDependenciesNatureToDisplay"
                v-bind:available_natures="available_natures"
            />
            <show-closed-control />
        </div>
        <no-data-to-show-empty-state
            v-if="should_display_empty_state"
            v-bind:should_invite_to_come_back="false"
        />
        <div class="roadmap-gantt" v-else>
            <template v-for="(row, index) in sorted_rows">
                <subtask-message
                    v-if="isErrorRow(row) || isEmptySubtasksRow(row)"
                    v-bind:key="'message-' + index"
                    v-bind:row="row"
                    v-bind:dimensions_map="dimensions_map"
                    v-bind:nb_iterations_ribbons="nb_iterations_ribbons"
                />
            </template>
            <div class="roadmap-gantt-header" v-bind:class="header_class" data-test="gantt-header">
                <template v-for="(row, index) in sorted_rows">
                    <task-header
                        v-if="isTaskRow(row)"
                        v-bind:key="'header-task-' + row.task.id"
                        v-bind:task="row.task"
                        v-bind:popover_element_id="getIdForPopover(row.task)"
                        v-show="row.is_shown"
                    />
                    <subtask-header
                        v-else-if="isSubtaskRow(row)"
                        v-bind:key="'header-task-' + row.parent.id + '-subtask-' + row.subtask.id"
                        v-bind:row="row"
                        v-bind:popover_element_id="
                            getIdForPopoverForSubtask(row.parent, row.subtask)
                        "
                        v-show="row.is_shown"
                    />
                    <subtask-message-header
                        v-else-if="isErrorRow(row) || isEmptySubtasksRow(row)"
                        v-bind:key="'header-message-' + row.for_task.id + '-' + index"
                        v-bind:row="row"
                        v-bind:dimensions_map="dimensions_map"
                        v-show="row.is_shown"
                    />
                    <subtask-skeleton-header
                        v-else
                        v-bind:key="'header-skeleton-' + row.for_task.id + '-' + index"
                        v-bind:skeleton="row"
                        v-show="row.is_shown"
                    />
                </template>
            </div>
            <scrolling-area v-bind:timescale="timescale" v-on:is_scrolling="isScrolling">
                <time-period-header
                    v-bind:nb_additional_units="nb_additional_units"
                    ref="time_period_header"
                />
                <iterations-ribbon
                    v-if="has_lvl1_iterations"
                    v-bind:nb_additional_units="nb_additional_units"
                    v-bind:level="1"
                    v-bind:iterations="lvl1_iterations_to_display"
                />
                <iterations-ribbon
                    v-if="has_lvl2_iterations"
                    v-bind:nb_additional_units="nb_additional_units"
                    v-bind:level="2"
                    v-bind:iterations="lvl2_iterations_to_display"
                />
                <template v-for="(row, index) in sorted_rows">
                    <gantt-task
                        v-if="isTaskRow(row)"
                        v-bind:key="'body-task-' + row.task.id"
                        v-bind:task="row.task"
                        v-bind:nb_additional_units="nb_additional_units"
                        v-bind:dependencies="dependencies"
                        v-bind:dimensions_map="dimensions_map"
                        v-bind:dependencies_nature_to_display="dependencies_nature_to_display"
                        v-bind:popover_element_id="getIdForPopover(row.task)"
                        v-show="row.is_shown"
                    />
                    <gantt-task
                        v-else-if="isSubtaskRow(row)"
                        v-bind:key="'body-task-' + row.parent.id + '-subtask-' + row.subtask.id"
                        v-bind:task="row.subtask"
                        v-bind:nb_additional_units="nb_additional_units"
                        v-bind:dependencies="dependencies"
                        v-bind:dimensions_map="dimensions_map"
                        v-bind:dependencies_nature_to_display="dependencies_nature_to_display"
                        v-bind:popover_element_id="
                            getIdForPopoverForSubtask(row.parent, row.subtask)
                        "
                        v-show="row.is_shown"
                    />
                    <div
                        v-else-if="isErrorRow(row) || isEmptySubtasksRow(row)"
                        v-bind:key="'body-message-' + row.for_task.id + '-' + index"
                        class="roadmap-gantt-task"
                        v-show="row.is_shown"
                    ></div>
                    <subtask-skeleton-bar
                        v-else
                        v-bind:key="'body-skeleton-' + row.for_task.id + '-' + index"
                        v-bind:nb_additional_units="nb_additional_units"
                        v-show="row.is_shown"
                    />
                </template>
            </scrolling-area>
            <template v-for="row in sorted_rows">
                <bar-popover
                    v-if="isTaskRow(row)"
                    v-bind:key="'popover-' + row.task.id"
                    v-bind:task="row.task"
                    v-bind:id="getIdForPopover(row.task)"
                    v-show="row.is_shown"
                />
                <bar-popover
                    v-if="isSubtaskRow(row)"
                    v-bind:key="'popover-' + row.parent.id + '-subtask-' + row.subtask.id"
                    v-bind:task="row.subtask"
                    v-bind:id="getIdForPopoverForSubtask(row.parent, row.subtask)"
                    v-show="row.is_shown"
                />
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick, computed } from "vue";
import {
    useStore,
    useNamespacedGetters,
    useNamespacedState,
    useNamespacedMutations,
} from "vuex-composition-helpers";
import GanttTask from "./Task/GanttTask.vue";
import type {
    NaturesLabels,
    Task,
    Row,
    TaskRow,
    SubtaskRow,
    ErrorRow,
    EmptySubtasksRow,
} from "../../type";
import TimePeriodHeader from "./TimePeriod/TimePeriodHeader.vue";
import { Styles } from "../../helpers/styles";
import { getTasksDependencies } from "../../helpers/dependency-map-builder";
import { getDimensionsMap } from "../../helpers/tasks-dimensions";
import TimePeriodControl from "./TimePeriod/TimePeriodControl.vue";
import DependencyNatureControl from "./DependencyNatureControl.vue";
import { getNatureLabelsForTasks } from "../../helpers/natures-labels-for-tasks";
import TaskHeader from "./Task/TaskHeader.vue";
import ScrollingArea from "./ScrollingArea.vue";
import BarPopover from "./Task/BarPopover.vue";
import { getUniqueId } from "../../helpers/uniq-id-generator";
import SubtaskSkeletonHeader from "./Subtask/SubtaskSkeletonHeader.vue";
import SubtaskSkeletonBar from "./Subtask/SubtaskSkeletonBar.vue";
import SubtaskHeader from "./Subtask/SubtaskHeader.vue";
import SubtaskMessage from "./Subtask/SubtaskMessage.vue";
import SubtaskMessageHeader from "./Subtask/SubtaskMessageHeader.vue";
import IterationsRibbon from "./Iteration/IterationsRibbon.vue";
import ShowClosedControl from "./ShowClosedControl.vue";
import NoDataToShowEmptyState from "../NoDataToShowEmptyState.vue";
import { sortRows } from "../../helpers/rows-sorter";

const store = useStore();
const { has_at_least_one_row_shown } = useNamespacedGetters("tasks", [
    "has_at_least_one_row_shown",
]);
const { timescale } = useNamespacedState("timeperiod", ["timescale"]);
const { setTimescale } = useNamespacedMutations("timeperiod", ["setTimescale"]);
const { time_period } = useNamespacedGetters("timeperiod", ["time_period"]);

const rows = computed(() => store.getters["tasks/rows"]);
const tasks = computed(() => store.getters["tasks/tasks"]);
const lvl1_iterations_to_display = computed(
    () => store.getters["iterations/lvl1_iterations_to_display"],
);
const lvl2_iterations_to_display = computed(
    () => store.getters["iterations/lvl2_iterations_to_display"],
);

const props = defineProps<{
    visible_natures: NaturesLabels;
}>();

const time_period_header = ref<InstanceType<typeof TimePeriodHeader>>();
const nb_additional_units = ref(0);
const observer = ref<ResizeObserver | null>(null);
const dependencies_nature_to_display = ref<string | null>(null);
const reactive_is_scrolling = ref(false);

const id_prefix_for_bar_popover = getUniqueId("roadmap-gantt-bar-popover");

onMounted(() => {
    observer.value = new ResizeObserver(adjustAdditionalUnits);
    if (time_period_header.value) {
        observer.value.observe(time_period_header.value.$el);
    }
});

onBeforeUnmount(() => {
    observer.value?.disconnect();
});

watch(rows, () => {
    observeTimeperiod();
});

function observeTimeperiod(): void {
    if (!time_period_header.value) {
        nextTick(() => {
            if (time_period_header.value && observer.value) {
                observer.value.observe(time_period_header.value.$el);
            }
        });
    }
}

function getIdForPopover(task: Task): string {
    return id_prefix_for_bar_popover + "-" + task.id;
}

function getIdForPopoverForSubtask(parent: Task, subtask: Task): string {
    return id_prefix_for_bar_popover + "-" + parent.id + "-" + subtask.id;
}

function isScrolling(is_scrolling: boolean): void {
    reactive_is_scrolling.value = is_scrolling;
}

function adjustAdditionalUnits(entries: ResizeObserverEntry[]): void {
    if (time_period.value.units.length === 0) {
        return;
    }

    const entry = entries.find(
        (entry) => time_period_header.value && entry.target === time_period_header.value.$el,
    );
    if (!entry) {
        return;
    }

    setAdditionalUnitsNumberAccordingToWidth(entry.contentRect.width);
}

watch(timescale, () => {
    adjustAdditionalUnitsAfterTimescaleChang();
});

function adjustAdditionalUnitsAfterTimescaleChang(): void {
    if (!time_period_header.value) {
        return;
    }

    setAdditionalUnitsNumberAccordingToWidth(
        time_period_header.value.$el.getBoundingClientRect().width,
    );
}

function setAdditionalUnitsNumberAccordingToWidth(width: number): void {
    const nb_visible_units = Math.ceil(width / Styles.TIME_UNIT_WIDTH_IN_PX);

    nb_additional_units.value = nb_visible_units - time_period.value.units.length - 1;
}

const should_display_empty_state = computed(() => !has_at_least_one_row_shown.value);
const dependencies = computed(() => getTasksDependencies(tasks.value));
const dimensions_map = computed(() => getDimensionsMap(rows.value, time_period.value));
const sorted_rows = computed(() => sortRows(rows.value));
const available_natures = computed(() =>
    getNatureLabelsForTasks(tasks.value, dependencies.value, props.visible_natures),
);
const has_lvl1_iterations = computed((): boolean => {
    return lvl1_iterations_to_display.value.length > 0;
});
const has_lvl2_iterations = computed((): boolean => {
    return lvl2_iterations_to_display.value.length > 0;
});
const nb_iterations_ribbons = computed((): number => {
    if (has_lvl1_iterations.value && has_lvl2_iterations.value) {
        return 2;
    }

    if (has_lvl1_iterations.value || has_lvl2_iterations.value) {
        return 1;
    }

    return 0;
});
const header_class = computed(() => {
    const classes = [];

    if (reactive_is_scrolling.value) {
        classes.push("roadmap-gantt-header-is-scrolling");
    }

    if (nb_iterations_ribbons.value) {
        classes.push(`roadmap-gantt-header-with-${nb_iterations_ribbons.value}-ribbons`);
    }

    return classes;
});

function isTaskRow(row: Row): row is TaskRow {
    return "task" in row;
}

function isSubtaskRow(row: Row): row is SubtaskRow {
    return "subtask" in row;
}

function isErrorRow(row: Row): row is ErrorRow {
    return "is_error" in row && row.is_error;
}

function isEmptySubtasksRow(row: Row): row is EmptySubtasksRow {
    return "is_empty" in row && row.is_empty;
}

function updateDependenciesNatureToDisplay(value: string | null): void {
    dependencies_nature_to_display.value = value;
}
</script>
