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
    <div class="roadmap-gantt-timeperiod">
        <div
            class="roadmap-gantt-timeperiod-unit"
            v-for="unit in time_units"
            v-bind:key="String(unit.toISO())"
            v-bind:title="time_period.formatLong(unit)"
            v-bind:class="time_period.getEvenOddClass(unit)"
        >
            {{ time_period.formatShort(unit) }}
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { TimePeriod } from "../../../type";
import type { DateTime } from "luxon";

@Component
export default class TimePeriodUnits extends Vue {
    @Prop({ required: true })
    readonly time_period!: TimePeriod;

    @Prop({ required: true })
    readonly time_units!: DateTime[];
}
</script>
