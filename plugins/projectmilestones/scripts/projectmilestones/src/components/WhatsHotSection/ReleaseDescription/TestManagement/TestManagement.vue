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

<script setup lang="ts">
import { computed, onMounted } from "vue";
import type { MilestoneData, TestManagementCampaign } from "../../../../type";
import type { DataPieChart } from "@tuleap/pie-chart";
import { createPieChart } from "../../../../chart_builder/pie_chart_drawer/pie-chart-drawer";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import { is_testplan_activated } from "../../../../helpers/test-management-helper";

const props = defineProps<{
    release_data: MilestoneData;
    campaign: TestManagementCampaign | null;
}>();

const { $gettext } = useGettext();

const PIE_CHART_HEIGHT_WIDTH = 170;
const PIE_CHART_RADIUS = 170;

const getSizes = computed((): { width: number; height: number; radius: number } => {
    return {
        width: PIE_CHART_HEIGHT_WIDTH,
        height: PIE_CHART_HEIGHT_WIDTH,
        radius: PIE_CHART_RADIUS,
    };
});
const getDataPieChartCampaign = computed((): DataPieChart[] => {
    if (!props.campaign) {
        return [];
    }

    return [
        {
            key: "notrun",
            label: $gettext("Not run"),
            count: props.campaign.nb_of_notrun,
        },
        {
            key: "passed",
            label: $gettext("Passed"),
            count: props.campaign.nb_of_passed,
        },
        {
            key: "failed",
            label: $gettext("Failed"),
            count: props.campaign.nb_of_failed,
        },
        {
            key: "blocked",
            label: $gettext("Blocked"),
            count: props.campaign.nb_of_blocked,
        },
    ];
});

onMounted((): void => {
    if (props.campaign) {
        const chart_container = document.getElementById(
            "release-widget-pie-chart-ttm-" + props.release_data.id,
        );

        if (chart_container) {
            createPieChart(chart_container, getSizes.value, getDataPieChartCampaign.value);
        }
    }
});

const is_testmanagement_available = computed((): boolean => {
    return is_testplan_activated(props.release_data) && props.campaign !== null;
});
</script>
