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
    <div class="roadmap-gantt-ribbon-iterations">
        <background-grid
            v-bind:time_period="time_period"
            v-bind:nb_additional_units="nb_additional_units"
        />
        <iteration-bar
            v-for="iteration of iterations"
            v-bind:key="iteration.id"
            v-bind:iteration="iteration"
            v-bind:level="level"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import BackgroundGrid from "../Task/BackgroundGrid.vue";
import type { Iteration, IterationLevel, TimePeriod } from "../../../type";
import { namespace } from "vuex-class";
import IterationBar from "./IterationBar.vue";

const timeperiod = namespace("timeperiod");

@Component({
    components: { IterationBar, BackgroundGrid },
})
export default class IterationsRibbon extends Vue {
    @timeperiod.Getter
    readonly time_period!: TimePeriod;

    @Prop({ required: true })
    readonly nb_additional_units!: number;

    @Prop({ required: true })
    readonly level!: IterationLevel;

    @Prop({ required: true })
    readonly iterations!: Iteration[];
}
</script>
