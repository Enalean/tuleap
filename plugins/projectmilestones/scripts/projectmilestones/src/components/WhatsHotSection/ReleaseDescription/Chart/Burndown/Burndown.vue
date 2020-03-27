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

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../../../../type";
import Vue from "vue";
import { createBurndownChart } from "../../../../../chart_builder/burndown_chart_builder/burndown-chart-drawer";
import { ChartPropsWhithoutTooltip } from "../../../../../../../../../../src/www/scripts/charts-builders/type";

@Component
export default class Burndown extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;

    getChartProps(container_width: number, container_height: number): ChartPropsWhithoutTooltip {
        return {
            graph_width: container_width,
            graph_height: container_height,
            margins: {
                top: 25,
                right: 30,
                bottom: 30,
                left: 35,
            },
        };
    }

    mounted(): void {
        if (!this.release_data.burndown_data) {
            return;
        }

        const chart_container = document.getElementById("chart-burndown-" + this.release_data.id);

        if (chart_container) {
            createBurndownChart(
                chart_container,
                this.getChartProps(chart_container.clientWidth, chart_container.clientHeight),
                this.release_data.burndown_data,
                this.release_data.id
            );
        }
    }
}
</script>
