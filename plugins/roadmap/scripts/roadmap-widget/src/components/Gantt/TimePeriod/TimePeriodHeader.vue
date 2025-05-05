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
        <time-period-years v-bind:years="years" />
        <time-period-units v-bind:time_period="time_period" v-bind:time_units="time_units" />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useNamespacedGetters } from "vuex-composition-helpers";
import { NbUnitsPerYear } from "../../../type";
import TimePeriodYears from "./TimePeriodYears.vue";
import TimePeriodUnits from "./TimePeriodUnits.vue";

const { time_period } = useNamespacedGetters("timeperiod", ["time_period"]);

const props = defineProps<{
    nb_additional_units: number;
}>();

const time_units = computed(() => {
    return [
        ...time_period.value.units,
        ...time_period.value.additionalUnits(props.nb_additional_units),
    ];
});

const years = computed(() => {
    return time_units.value.reduce((nb_units_per_year, unit): NbUnitsPerYear => {
        const year = unit.year;
        nb_units_per_year.set(year, (nb_units_per_year.get(year) || 0) + 1);

        return nb_units_per_year;
    }, new NbUnitsPerYear());
});
</script>
