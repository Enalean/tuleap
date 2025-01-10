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

<script setup lang="ts">
import BackgroundGrid from "../Task/BackgroundGrid.vue";
import type { Iteration, IterationLevel } from "../../../type";
import IterationBar from "./IterationBar.vue";
import { useNamespacedGetters } from "vuex-composition-helpers";
import type { TimeperiodGetters } from "../../../store/timeperiod/type";

defineProps<{
    nb_additional_units: number;
    level: IterationLevel;
    iterations: Iteration[];
}>();

const { time_period } = useNamespacedGetters<Pick<TimeperiodGetters, "time_period">>("timeperiod", [
    "time_period",
]);
</script>
