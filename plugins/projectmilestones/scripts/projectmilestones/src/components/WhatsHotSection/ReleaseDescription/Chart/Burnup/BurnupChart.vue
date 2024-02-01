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
  -
  -->

<template>
    <div class="release-chart-container">
        <svg class="release-chart" v-bind:id="`chart-burnup-${release_data.id}`"></svg>
    </div>
</template>

<script setup lang="ts">
import type { BurnupData, MilestoneData } from "../../../../../type";
import { onMounted } from "vue";
import { createBurnupChart } from "../../../../../chart_builder/burnup_chart_builder/burnup-chart-drawer";
import type { ChartPropsWithoutTooltip } from "@tuleap/chart-builder";
import { transformToGenericBurnupData } from "@tuleap/plugin-agiledashboard-burnup-data-transformer";
import { useStore } from "../../../../../stores/root";

const props = defineProps<{ release_data: MilestoneData; burnup_data: BurnupData | null }>();

const root_store = useStore();

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
    if (!props.burnup_data) {
        return;
    }

    const generic_burnup_data = transformToGenericBurnupData(
        props.burnup_data,
        root_store.burnup_mode,
    );
    const chart_container = document.getElementById("chart-burnup-" + props.release_data.id);

    if (chart_container) {
        createBurnupChart(
            chart_container,
            getChartProps(chart_container.clientWidth, chart_container.clientHeight),
            generic_burnup_data,
        );
    }
});
</script>
