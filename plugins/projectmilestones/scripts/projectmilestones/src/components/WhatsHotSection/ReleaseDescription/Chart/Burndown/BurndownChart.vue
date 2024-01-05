<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
  -->
<template>
    <div class="release-chart-container">
        <svg class="release-chart" v-bind:id="`chart-burndown-${release_data.id}`"></svg>
    </div>
</template>
<script setup lang="ts">
import type { BurndownData, MilestoneData } from "../../../../../type";
import { onMounted } from "vue";
import { createBurndownChart } from "../../../../../chart_builder/burndown_chart_builder/burndown-chart-drawer";
import type { ChartPropsWithoutTooltip } from "@tuleap/chart-builder";

const props = defineProps<{ release_data: MilestoneData; burndown_data: BurndownData | null }>();

function getChartProps(
    container_width: number,
    container_height: number,
): ChartPropsWithoutTooltip {
    return {
        graph_width: container_width,
        graph_height: container_height,
        margins: {
            top: 10,
            right: 30,
            bottom: 20,
            left: 25,
        },
    };
}

onMounted((): void => {
    if (!props.burndown_data) {
        return;
    }
    const chart_container = document.getElementById("chart-burndown-" + props.release_data.id);
    if (chart_container) {
        createBurndownChart(
            chart_container,
            getChartProps(chart_container.clientWidth, chart_container.clientHeight),
            props.burndown_data,
            props.release_data.id,
        );
    }
});
</script>
