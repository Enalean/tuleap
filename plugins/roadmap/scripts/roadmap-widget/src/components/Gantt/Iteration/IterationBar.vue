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
        v-bind:class="'roadmap-gantt-ribbon-iterations-iteration-level-' + level"
        v-bind:style="style"
        v-bind:title="iteration.title"
    >
        <a v-bind:href="iteration.html_url">{{ iteration.title }}</a>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Iteration, IterationLevel, Row, TimePeriod } from "../../../type";
import { getLeftForDate } from "../../../helpers/left-postion";
import { namespace } from "vuex-class";
import { Styles } from "../../../helpers/styles";

const tasks = namespace("tasks");
const iterations = namespace("iterations");
const timeperiod = namespace("timeperiod");

@Component
export default class IterationBar extends Vue {
    @Prop({ required: true })
    readonly level!: IterationLevel;

    @Prop({ required: true })
    readonly iteration!: Iteration;

    @timeperiod.Getter
    private readonly time_period!: TimePeriod;

    @iterations.State
    private readonly lvl2_iterations!: Iteration[];

    @tasks.Getter
    private readonly rows!: Row[];

    get style(): string {
        const left = getLeftForDate(this.iteration.start, this.time_period);

        const task_end_date = new Date(this.iteration.end);
        const task_end_date_plus_one_day = new Date(
            task_end_date.setUTCDate(task_end_date.getUTCDate() + 1),
        );
        const width = getLeftForDate(task_end_date_plus_one_day, this.time_period) - left;

        const height =
            this.nb_ribbons_to_take_into_account * Styles.ITERATION_HEIGHT_IN_PX +
            this.nb_visible_rows * Styles.TASK_HEIGHT_IN_PX -
            Styles.MARGIN_BETWEEN_ITERATIONS_IN_PX;

        return `left: ${left}px; width: ${width}px; height: ${height}px;`;
    }

    get nb_visible_rows(): number {
        return this.rows.filter((row) => row.is_shown).length;
    }

    get nb_ribbons_to_take_into_account(): number {
        if (this.level === 2) {
            return 1;
        }

        if (this.lvl2_iterations.length > 0) {
            return 2;
        }

        return 1;
    }
}
</script>
