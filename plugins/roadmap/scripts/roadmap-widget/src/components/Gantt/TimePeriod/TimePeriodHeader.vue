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

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { NbUnitsPerYear } from "../../../type";
import type { TimePeriod } from "../../../type";
import TimePeriodUnits from "./TimePeriodUnits.vue";
import TimePeriodYears from "./TimePeriodYears.vue";
import { namespace } from "vuex-class";
import type { DateTime } from "luxon";

const timeperiod = namespace("timeperiod");

@Component({
    components: { TimePeriodYears, TimePeriodUnits },
})
export default class TimePeriodHeader extends Vue {
    @timeperiod.Getter
    readonly time_period!: TimePeriod;

    @Prop({ required: true })
    readonly nb_additional_units!: number;

    get time_units(): DateTime[] {
        return [
            ...this.time_period.units,
            ...this.time_period.additionalUnits(this.nb_additional_units),
        ];
    }

    get years(): NbUnitsPerYear {
        return this.time_units.reduce((nb_units_per_year, unit): NbUnitsPerYear => {
            const year = unit.year;
            nb_units_per_year.set(year, (nb_units_per_year.get(year) || 0) + 1);

            return nb_units_per_year;
        }, new NbUnitsPerYear());
    }
}
</script>
