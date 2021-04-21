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
            <time-period-control v-model="timescale" />
            <dependency-nature-control
                v-model="dependencies_nature_to_display"
                v-bind:available_natures="available_natures"
            />
        </div>
        <div class="roadmap-gantt">
            <div class="roadmap-gantt-header" v-bind:class="header_class" data-test="gantt-header">
                <task-header v-for="task of tasks" v-bind:key="task.id" v-bind:task="task" />
            </div>
            <scrolling-area
                v-bind:time_period="time_period"
                v-bind:now="now"
                v-on:is_scrolling="isScrolling"
            >
                <time-period-header
                    v-bind:time_period="time_period"
                    v-bind:nb_additional_units="nb_additional_units"
                    ref="time_period"
                />
                <gantt-task
                    v-for="task of tasks"
                    v-bind:key="task.id"
                    v-bind:task="task"
                    v-bind:time_period="time_period"
                    v-bind:nb_additional_units="nb_additional_units"
                    v-bind:dependencies="dependencies"
                    v-bind:dimensions_map="dimensions_map"
                    v-bind:dependencies_nature_to_display="dependencies_nature_to_display"
                    v-bind:popover_element_id="getIdForPopover(task)"
                />
            </scrolling-area>
            <bar-popover
                v-for="task of tasks"
                v-bind:key="task.id"
                v-bind:task="task"
                v-bind:id="getIdForPopover(task)"
            />
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { ResizeObserver as ResizeObserverPolyfill } from "@juggle/resize-observer";
import { Component, Prop, Watch } from "vue-property-decorator";
import GanttTask from "./Task/GanttTask.vue";
import type {
    NaturesLabels,
    Task,
    TimePeriod,
    TasksDependencies,
    TaskDimensionMap,
    TimeScale,
} from "../../type";
import TimePeriodHeader from "./TimePeriod/TimePeriodHeader.vue";
import { getFirstDate } from "../../helpers/first-date";
import { getLastDate } from "../../helpers/last-date";
import TodayIndicator from "./TodayIndicator.vue";
import { Styles } from "../../helpers/styles";
import { TimePeriodQuarter } from "../../helpers/time-period-quarter";
import { getTasksDependencies } from "../../helpers/dependency-map-builder";
import { getDimensionsMap } from "../../helpers/tasks-dimensions";
import { TimePeriodMonth } from "../../helpers/time-period-month";
import TimePeriodControl from "./TimePeriod/TimePeriodControl.vue";
import DependencyNatureControl from "./DependencyNatureControl.vue";
import { getNatureLabelsForTasks } from "../../helpers/natures-labels-for-tasks";
import { TimePeriodWeek } from "../../helpers/time-period-week";
import TaskHeader from "./Task/TaskHeader.vue";
import ScrollingArea from "./ScrollingArea.vue";
import BarPopover from "./Task/BarPopover.vue";
import { getUniqueId } from "../../helpers/uniq-id-generator";
import { State } from "vuex-class";

@Component({
    components: {
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
    $refs!: {
        time_period: TimePeriodHeader;
    };

    @Prop({ required: true })
    readonly tasks!: Task[];

    @State
    private readonly locale_bcp47!: string;

    @Prop({ required: true })
    private readonly visible_natures!: NaturesLabels;

    private nb_additional_units = 0;

    private observer: ResizeObserver | null = null;

    private now = new Date();

    private timescale: TimeScale = "month";

    private dependencies_nature_to_display: string | null = null;

    private is_scrolling = false;

    private id_prefix_for_bar_popover = getUniqueId("roadmap-gantt-bar-popover");

    mounted(): void {
        const ResizeObserverImplementation = window.ResizeObserver || ResizeObserverPolyfill;
        this.observer = new ResizeObserverImplementation(this.adjustAdditionalUnits);
        this.observer.observe(this.$refs.time_period.$el);
    }

    beforeDestroy(): void {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    getIdForPopover(task: Task): string {
        return this.id_prefix_for_bar_popover + "-" + task.id;
    }

    isScrolling(is_scrolling: boolean): void {
        this.is_scrolling = is_scrolling;
    }

    adjustAdditionalUnits(entries: ResizeObserverEntry[]): void {
        if (this.time_period.units.length === 0) {
            return;
        }

        const entry = entries.find((entry) => entry.target === this.$refs.time_period.$el);
        if (!entry) {
            return;
        }

        this.setAdditionalUnitsNumberAccordingToWidth(entry.contentRect.width);
    }

    @Watch("timescale")
    adjustAdditionalUnitsAfterTimescaleChang(): void {
        this.setAdditionalUnitsNumberAccordingToWidth(
            this.$refs.time_period.$el.getBoundingClientRect().width
        );
    }

    setAdditionalUnitsNumberAccordingToWidth(width: number): void {
        const nb_visible_units = Math.ceil(width / Styles.TIME_UNIT_WIDTH_IN_PX);

        this.nb_additional_units = nb_visible_units - this.time_period.units.length - 1;
    }

    get time_period(): TimePeriod {
        if (this.timescale === "week") {
            return new TimePeriodWeek(
                getFirstDate(this.tasks, this.now),
                getLastDate(this.tasks, this.now),
                this
            );
        }

        if (this.timescale === "quarter") {
            return new TimePeriodQuarter(
                getFirstDate(this.tasks, this.now),
                getLastDate(this.tasks, this.now),
                this
            );
        }

        return new TimePeriodMonth(
            getFirstDate(this.tasks, this.now),
            getLastDate(this.tasks, this.now),
            this.locale_bcp47
        );
    }

    get dependencies(): TasksDependencies {
        return getTasksDependencies(this.tasks);
    }

    get dimensions_map(): TaskDimensionMap {
        return getDimensionsMap(this.tasks, this.time_period);
    }

    get available_natures(): NaturesLabels {
        return getNatureLabelsForTasks(this.tasks, this.dependencies, this.visible_natures);
    }

    get header_class(): string {
        return this.is_scrolling ? "roadmap-gantt-header-is-scrolling" : "";
    }
}
</script>
