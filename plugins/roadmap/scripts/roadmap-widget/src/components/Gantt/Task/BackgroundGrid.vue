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
    <div class="roadmap-gantt-task-background-grid">
        <div
            class="roadmap-gantt-task-background-grid-unit"
            v-for="unit in time_units"
            v-bind:key="'grid-month-' + unit.toISO()"
            v-bind:class="time_period.getEvenOddClass(unit)"
        />
    </div>
</template>

<script setup lang="ts">
import type { TimePeriod } from "../../../type";
import type { DateTime } from "luxon";
import { computed } from "vue";

const props = defineProps<{
    time_period: TimePeriod;
    nb_additional_units: number;
}>();

const time_units = computed((): DateTime[] => {
    return [
        ...props.time_period.units,
        ...props.time_period.additionalUnits(props.nb_additional_units),
    ];
});
</script>
