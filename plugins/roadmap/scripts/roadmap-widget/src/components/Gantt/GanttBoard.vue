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
    <div class="roadmap-gantt">
        <time-period-month v-bind:months="time_units" v-bind:locale="locale" ref="time_period" />
        <div>
            <gantt-task
                v-for="task of tasks"
                v-bind:key="task.id"
                v-bind:task="task"
                v-bind:time_units="time_units"
            />
        </div>
        <today-indicator v-bind:locale="locale" v-bind:months="months" v-bind:now="now" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import GanttTask from "./Task/GanttTask.vue";
import type { Task } from "../../type";
import TimePeriodMonth from "./TimePeriod/TimePeriodMonth.vue";
import { getMonths } from "../../helpers/months";
import { getFirstDate } from "../../helpers/first-date";
import { getLastDate } from "../../helpers/last-date";
import { getAdditionalMonths } from "../../helpers/additional-months";
import TodayIndicator from "./TodayIndicator.vue";
import { Styles } from "../../helpers/styles";

@Component({
    components: { TodayIndicator, TimePeriodMonth, GanttTask },
})
export default class GanttBoard extends Vue {
    $refs!: {
        time_period: TimePeriodMonth;
    };

    @Prop({ required: true })
    readonly tasks!: Task[];

    @Prop({ required: true })
    private readonly locale!: string;

    private additional_months: Date[] = [];

    private observer: ResizeObserver | null = null;

    private now = new Date();

    mounted(): void {
        this.observer = new ResizeObserver(this.adjustAdditionalMonths);
        this.observer.observe(this.$refs.time_period.$el);
    }

    beforeDestroy(): void {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    adjustAdditionalMonths(entries: ResizeObserverEntry[]): void {
        if (this.months.length === 0) {
            return;
        }

        for (const entry of entries) {
            if (entry.target !== this.$refs.time_period.$el) {
                continue;
            }

            const nb_visible_months = Math.ceil(
                entry.contentRect.width / Styles.TIME_UNIT_WIDTH_IN_PX
            );
            const nb_missing_months = nb_visible_months - this.months.length - 1;

            this.additional_months = getAdditionalMonths(
                this.months[this.months.length - 1],
                nb_missing_months
            );
        }
    }

    get months(): Date[] {
        return getMonths(getFirstDate(this.tasks), getLastDate(this.tasks), this.now);
    }

    get time_units(): Date[] {
        return [...this.months, ...this.additional_months];
    }
}
</script>
