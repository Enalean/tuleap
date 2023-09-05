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
        <div class="roadmap-gantt-task-bar-container" v-bind:style="randomStyleLeft()">
            <div class="roadmap-gantt-task-bar tlp-skeleton-text" v-bind:style="randomStyleWidth()">
                <div class="roadmap-gantt-task-bar-progress"></div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Styles } from "../../../helpers/styles";
import BackgroundGrid from "../Task/BackgroundGrid.vue";
import type { TimePeriod } from "../../../type";
import { namespace } from "vuex-class";

const timeperiod = namespace("timeperiod");

@Component({
    components: { BackgroundGrid },
})
export default class SubtaskSkeletonBar extends Vue {
    @timeperiod.Getter
    readonly time_period!: TimePeriod;

    @Prop({ required: true })
    readonly nb_additional_units!: number;

    randomStyleLeft(): string {
        const left = this.getRandomInt(
            40,
            (this.time_period.units.length - 3) * Styles.TIME_UNIT_WIDTH_IN_PX,
        );

        return `left: ${left}px;`;
    }

    randomStyleWidth(): string {
        const width = this.getRandomInt(
            30,
            (this.time_period.units.length * Styles.TIME_UNIT_WIDTH_IN_PX) / 3,
        );

        return `width: ${width}px;`;
    }

    getRandomInt(min: number, max: number): number {
        return Math.floor(Math.random() * (max - min) + min);
    }
}
</script>
