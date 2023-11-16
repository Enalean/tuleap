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
    <div
        v-if="is_testmanagement_available"
        v-bind:id="`release-widget-pie-chart-ttm-${release_data.id}`"
        class="release-widget-pie-chart-ttm"
        data-test="display-ttm"
    ></div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { MilestoneData, TestManagementCampaign } from "../../../../type";
import { is_testplan_activated } from "../../../../helpers/test-management-helper";
import type { DataPieChart } from "@tuleap/pie-chart";
import { createPieChart } from "../../../../chart_builder/pie_chart_drawer/pie-chart-drawer";

@Component({})
export default class TestManagement extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @Prop()
    readonly campaign!: TestManagementCampaign;

    PIE_CHART_HEIGHT_WIDTH = 170;
    PIE_CHART_RADIUS = 170;

    get getSizes(): { width: number; height: number; radius: number } {
        return {
            width: this.PIE_CHART_HEIGHT_WIDTH,
            height: this.PIE_CHART_HEIGHT_WIDTH,
            radius: this.PIE_CHART_RADIUS,
        };
    }

    get getDataPieChartCampaign(): DataPieChart[] {
        if (!this.campaign) {
            return [];
        }

        return [
            {
                key: "notrun",
                label: this.$gettext("Not run"),
                count: this.campaign.nb_of_notrun,
            },
            {
                key: "passed",
                label: this.$gettext("Passed"),
                count: this.campaign.nb_of_passed,
            },
            {
                key: "failed",
                label: this.$gettext("Failed"),
                count: this.campaign.nb_of_failed,
            },
            {
                key: "blocked",
                label: this.$gettext("Blocked"),
                count: this.campaign.nb_of_blocked,
            },
        ];
    }

    mounted(): void {
        if (this.campaign) {
            const chart_container = document.getElementById(
                "release-widget-pie-chart-ttm-" + this.release_data.id,
            );

            if (chart_container) {
                createPieChart(chart_container, this.getSizes, this.getDataPieChartCampaign);
            }
        }
    }

    get is_testmanagement_available(): boolean {
        return is_testplan_activated(this.release_data) && this.campaign !== null;
    }
}
</script>
