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
        <time-period-header
            v-bind:time_period="time_period"
            v-bind:nb_additional_units="nb_additional_units"
            v-bind:locale="locale"
            ref="time_period"
        />
        <div>
            <gantt-task
                v-for="task of tasks"
                v-bind:key="task.id"
                v-bind:task="task"
                v-bind:time_period="time_period"
                v-bind:nb_additional_units="nb_additional_units"
            />
        </div>
        <today-indicator v-bind:locale="locale" v-bind:time_period="time_period" v-bind:now="now" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import GanttTask from "./Task/GanttTask.vue";
import type { Task, TimePeriod } from "../../type";
import TimePeriodHeader from "./TimePeriod/TimePeriodHeader.vue";
import { getFirstDate } from "../../helpers/first-date";
import { getLastDate } from "../../helpers/last-date";
import TodayIndicator from "./TodayIndicator.vue";
import { Styles } from "../../helpers/styles";
import { TimePeriodMonth } from "../../helpers/time-period-month";

@Component({
    components: { TodayIndicator, TimePeriodHeader, GanttTask },
})
export default class GanttBoard extends Vue {
    $refs!: {
        time_period: TimePeriodHeader;
    };

    @Prop({ required: true })
    readonly tasks!: Task[];

    @Prop({ required: true })
    private readonly locale!: string;

    private nb_additional_units = 0;

    private observer: ResizeObserver | null = null;

    private now = new Date();

    mounted(): void {
        this.observer = new ResizeObserver(this.adjustAdditionalUnits);
        this.observer.observe(this.$refs.time_period.$el);
    }

    beforeDestroy(): void {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    adjustAdditionalUnits(entries: ResizeObserverEntry[]): void {
        if (this.time_period.units.length === 0) {
            return;
        }

        const entry = entries.find((entry) => entry.target === this.$refs.time_period.$el);
        if (!entry) {
            return;
        }

        const nb_visible_units = Math.ceil(entry.contentRect.width / Styles.TIME_UNIT_WIDTH_IN_PX);

        this.nb_additional_units = nb_visible_units - this.time_period.units.length - 1;
    }

    get time_period(): TimePeriod {
        return new TimePeriodMonth(
            getFirstDate(this.tasks),
            getLastDate(this.tasks),
            this.now,
            this.locale
        );
    }
}
</script>
