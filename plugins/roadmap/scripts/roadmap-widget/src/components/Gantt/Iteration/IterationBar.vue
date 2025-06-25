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

<script setup lang="ts">
import { computed } from "vue";
import type { Iteration, IterationLevel, Row } from "../../../type";
import { getLeftForDate } from "../../../helpers/left-position";
import { Styles } from "../../../helpers/styles";
import { useNamespacedGetters, useStore } from "vuex-composition-helpers";
import type { TimeperiodGetters } from "../../../store/timeperiod/type";

const props = defineProps<{
    level: IterationLevel;
    iteration: Iteration;
}>();

const store = useStore();

const { time_period } = useNamespacedGetters<Pick<TimeperiodGetters, "time_period">>("timeperiod", [
    "time_period",
]);

const rows = computed((): Row[] => store.getters["tasks/rows"]);
const lvl2_iterations = computed((): Iteration[] => store.state.iterations.lvl2_iterations);

const nb_visible_rows = computed((): number => {
    return rows.value.filter((row: Row) => row.is_shown).length;
});

const nb_ribbons_to_take_into_account = computed((): number => {
    if (props.level === 2) {
        return 1;
    }
    if (lvl2_iterations.value.length > 0) {
        return 2;
    }

    return 1;
});

const style = computed((): string => {
    const left = getLeftForDate(props.iteration.start, time_period.value);

    const task_end_date_plus_one_day = props.iteration.end.plus({ day: 1 });
    const width = getLeftForDate(task_end_date_plus_one_day, time_period.value) - left;

    const height =
        nb_ribbons_to_take_into_account.value * Styles.ITERATION_HEIGHT_IN_PX +
        nb_visible_rows.value * Styles.TASK_HEIGHT_IN_PX -
        Styles.MARGIN_BETWEEN_ITERATIONS_IN_PX;

    return `left: ${left}px; width: ${width}px; height: ${height}px;`;
});
</script>
