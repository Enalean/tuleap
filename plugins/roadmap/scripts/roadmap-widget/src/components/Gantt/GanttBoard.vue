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
            <time-period-control v-bind:value="timescale" v-on:input="setTimescale" />
            <dependency-nature-control
                v-model="dependencies_nature_to_display"
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
                    ref="time_period"
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

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import GanttTask from "./Task/GanttTask.vue";
import type {
    NaturesLabels,
    Task,
    TimePeriod,
    TasksDependencies,
    TaskDimensionMap,
    TimeScale,
    Row,
    TaskRow,
    SubtaskRow,
    ErrorRow,
    EmptySubtasksRow,
    Iteration,
} from "../../type";
import TimePeriodHeader from "./TimePeriod/TimePeriodHeader.vue";
import TodayIndicator from "./TodayIndicator.vue";
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
import { namespace, State } from "vuex-class";
import SubtaskSkeletonHeader from "./Subtask/SubtaskSkeletonHeader.vue";
import SubtaskSkeletonBar from "./Subtask/SubtaskSkeletonBar.vue";
import SubtaskHeader from "./Subtask/SubtaskHeader.vue";
import SubtaskMessage from "./Subtask/SubtaskMessage.vue";
import SubtaskMessageHeader from "./Subtask/SubtaskMessageHeader.vue";
import IterationsRibbon from "./Iteration/IterationsRibbon.vue";
import ShowClosedControl from "./ShowClosedControl.vue";
import NoDataToShowEmptyState from "../NoDataToShowEmptyState.vue";
import { sortRows } from "../../helpers/rows-sorter";

const tasks = namespace("tasks");
const iterations = namespace("iterations");
const timeperiod = namespace("timeperiod");

@Component({
    components: {
        NoDataToShowEmptyState,
        ShowClosedControl,
        IterationsRibbon,
        SubtaskMessageHeader,
        SubtaskMessage,
        SubtaskHeader,
        SubtaskSkeletonBar,
        SubtaskSkeletonHeader,
        BarPopover,
        ScrollingArea,
        TaskHeader,
        DependencyNatureControl,
        TimePeriodControl,
        TodayIndicator,
        TimePeriodHeader,
        GanttTask,
    },
})
export default class GanttBoard extends Vue {
    override $refs!: {
        time_period: TimePeriodHeader | undefined;
    };

    @tasks.Getter
    readonly rows!: Row[];

    @tasks.Getter
    private readonly has_at_least_one_row_shown!: boolean;

    @tasks.Getter
    private readonly tasks!: Task[];

    @iterations.Getter
    readonly lvl1_iterations_to_display!: Iteration[];

    @iterations.Getter
    readonly lvl2_iterations_to_display!: Iteration[];

    @timeperiod.State
    readonly timescale!: TimeScale;

    @timeperiod.Mutation
    readonly setTimescale!: (timescale: TimeScale) => void;

    @timeperiod.Getter
    private readonly time_period!: TimePeriod;

    @State
    private readonly locale_bcp47!: string;

    @State
    private readonly now!: Date;

    @Prop({ required: true })
    private readonly visible_natures!: NaturesLabels;

    nb_additional_units = 0;

    private observer: ResizeObserver | null = null;

    dependencies_nature_to_display: string | null = null;

    private is_scrolling = false;

    private id_prefix_for_bar_popover = getUniqueId("roadmap-gantt-bar-popover");

    mounted(): void {
        this.observer = new ResizeObserver(this.adjustAdditionalUnits);
        if (this.$refs.time_period) {
            this.observer.observe(this.$refs.time_period.$el);
        }
    }

    beforeDestroy(): void {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    @Watch("rows")
    observeTimeperiod(): void {
        if (!this.$refs.time_period) {
            this.$nextTick(() => {
                if (this.$refs.time_period && this.observer) {
                    this.observer.observe(this.$refs.time_period.$el);
                }
            });
        }
    }

    getIdForPopover(task: Task): string {
        return this.id_prefix_for_bar_popover + "-" + task.id;
    }

    getIdForPopoverForSubtask(parent: Task, subtask: Task): string {
        return this.id_prefix_for_bar_popover + "-" + parent.id + "-" + subtask.id;
    }

    isScrolling(is_scrolling: boolean): void {
        this.is_scrolling = is_scrolling;
    }

    adjustAdditionalUnits(entries: ResizeObserverEntry[]): void {
        if (this.time_period.units.length === 0) {
            return;
        }

        const entry = entries.find(
            (entry) => this.$refs.time_period && entry.target === this.$refs.time_period.$el,
        );
        if (!entry) {
            return;
        }

        this.setAdditionalUnitsNumberAccordingToWidth(entry.contentRect.width);
    }

    @Watch("timescale")
    adjustAdditionalUnitsAfterTimescaleChang(): void {
        if (!this.$refs.time_period) {
            return;
        }

        this.setAdditionalUnitsNumberAccordingToWidth(
            this.$refs.time_period.$el.getBoundingClientRect().width,
        );
    }

    setAdditionalUnitsNumberAccordingToWidth(width: number): void {
        const nb_visible_units = Math.ceil(width / Styles.TIME_UNIT_WIDTH_IN_PX);

        this.nb_additional_units = nb_visible_units - this.time_period.units.length - 1;
    }

    get should_display_empty_state(): boolean {
        return !this.has_at_least_one_row_shown;
    }

    get dependencies(): TasksDependencies {
        return getTasksDependencies(this.tasks);
    }

    get dimensions_map(): TaskDimensionMap {
        return getDimensionsMap(this.rows, this.time_period);
    }

    get sorted_rows(): Row[] {
        return sortRows(this.rows);
    }

    get available_natures(): NaturesLabels {
        return getNatureLabelsForTasks(this.tasks, this.dependencies, this.visible_natures);
    }

    get header_class(): string[] {
        const classes = [];

        if (this.is_scrolling) {
            classes.push("roadmap-gantt-header-is-scrolling");
        }

        if (this.nb_iterations_ribbons) {
            classes.push(`roadmap-gantt-header-with-${this.nb_iterations_ribbons}-ribbons`);
        }

        return classes;
    }

    get nb_iterations_ribbons(): number {
        if (this.has_lvl1_iterations && this.has_lvl2_iterations) {
            return 2;
        }

        if (this.has_lvl1_iterations || this.has_lvl2_iterations) {
            return 1;
        }

        return 0;
    }

    get has_lvl1_iterations(): boolean {
        return this.lvl1_iterations_to_display.length > 0;
    }

    get has_lvl2_iterations(): boolean {
        return this.lvl2_iterations_to_display.length > 0;
    }

    isTaskRow(row: Row): row is TaskRow {
        return "task" in row;
    }

    isSubtaskRow(row: Row): row is SubtaskRow {
        return "subtask" in row;
    }

    isErrorRow(row: Row): row is ErrorRow {
        return "is_error" in row && row.is_error;
    }

    isEmptySubtasksRow(row: Row): row is EmptySubtasksRow {
        return "is_empty" in row && row.is_empty;
    }
}
</script>
