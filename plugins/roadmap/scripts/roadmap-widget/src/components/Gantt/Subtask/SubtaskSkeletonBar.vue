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

<script setup lang="ts">
import { Styles } from "../../../helpers/styles";
import BackgroundGrid from "../Task/BackgroundGrid.vue";
import { useNamespacedGetters } from "vuex-composition-helpers";
import type { TimeperiodGetters } from "../../../store/timeperiod/type";

defineProps<{
    nb_additional_units: number;
}>();

const { time_period } = useNamespacedGetters<Pick<TimeperiodGetters, "time_period">>("timeperiod", [
    "time_period",
]);

function randomStyleLeft(): string {
    const left = getRandomInt(
        40,
        (time_period.value.units.length - 3) * Styles.TIME_UNIT_WIDTH_IN_PX,
    );

    return `left: ${left}px;`;
}

function randomStyleWidth(): string {
    const width = getRandomInt(
        30,
        (time_period.value.units.length * Styles.TIME_UNIT_WIDTH_IN_PX) / 3,
    );

    return `width: ${width}px;`;
}

function getRandomInt(min: number, max: number): number {
    return Math.floor(Math.random() * (max - min) + min);
}
</script>
