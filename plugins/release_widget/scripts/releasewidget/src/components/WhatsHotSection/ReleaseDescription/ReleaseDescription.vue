<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="release-content-description">
        <release-description-badges-tracker v-bind:release_data="release_data"/>
        <div class="release-description-row">
            <div v-if="release_data.description" class="tlp-tooltip tlp-tooltip-top" v-bind:data-tlp-tooltip="release_data.description" data-test="tooltip-description">
                <div class="release-description" v-dompurify-html="release_data.description"></div>
            </div>
            <a v-if="get_overview_link !== null" v-bind:href="get_overview_link" data-test="overview-link">
                <i class="release-description-link-icon fa fa-long-arrow-right"></i>
                <translate v-bind:translate-params="{label_tracker: label_tracker_planning}"> Go to %{label_tracker} overview </translate>
            </a>
        </div>
        <div class="release-chart-burndown-row">
            <h2 class="tlp-pane-subtitle" v-translate>Burndown</h2>
            <burndown-chart v-bind:release_data="release_data"/>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../../type";
import { State } from "vuex-class";
import ReleaseDescriptionBadgesTracker from "./ReleaseDescriptionBadgesTracker.vue";
import BurndownChart from "./Chart/BurndownChart.vue";

@Component({
    components: { ReleaseDescriptionBadgesTracker, BurndownChart }
})
export default class ReleaseDescription extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @State
    readonly project_id!: number;
    @State
    readonly label_tracker_planning!: string;

    get get_overview_link(): string | null {
        if (!this.release_data.planning) {
            return null;
        }
        return (
            "/plugins/agiledashboard/?group_id=" +
            encodeURIComponent(this.project_id) +
            "&planning_id=" +
            encodeURIComponent(this.release_data.planning.id) +
            "&action=show&aid=" +
            encodeURIComponent(this.release_data.id) +
            "&pane=details"
        );
    }
}
</script>
