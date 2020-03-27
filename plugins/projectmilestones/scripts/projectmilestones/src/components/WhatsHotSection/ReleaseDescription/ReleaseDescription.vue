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
    <div>
        <div class="release-content-description">
            <release-description-badges-tracker v-bind:release_data="release_data" />
            <chart-displayer class="release-charts-row" v-bind:release_data="release_data" />
        </div>
        <div class="release-description-row">
            <div
                v-if="release_data.description"
                class="tlp-tooltip tlp-tooltip-top"
                v-bind:data-tlp-tooltip="release_data.description"
                data-test="tooltip-description"
            >
                <div class="release-description" v-dompurify-html="release_data.description"></div>
            </div>
            <release-buttons-description v-bind:release_data="release_data">
                <a
                    v-if="get_planning_link"
                    v-bind:href="get_planning_link"
                    data-test="planning-link"
                    class="release-planning-link release-planning-link-item"
                >
                    <i class="release-description-link-icon fa fa-sign-in"></i>
                    <span class="release-planning-link-item-text">
                        <translate
                            v-bind:translate-params="{
                                label_submilestone: tracker_submilestone_label,
                            }"
                        >
                            %{label_submilestone} Planning
                        </translate>
                    </span>
                </a>
            </release-buttons-description>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../../type";
import ReleaseDescriptionBadgesTracker from "./ReleaseDescriptionBadgesTracker.vue";
import ReleaseButtonsDescription from "./ReleaseButtonsDescription.vue";
import ChartDisplayer from "./Chart/ChartDisplayer.vue";
import { State } from "vuex-class";

@Component({
    components: {
        ChartDisplayer,
        ReleaseButtonsDescription,
        ReleaseDescriptionBadgesTracker,
    },
})
export default class ReleaseDescription extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @State
    readonly project_id!: number;
    @State
    readonly user_can_view_sub_milestones_planning!: boolean;

    get get_planning_link(): string | null {
        if (
            !this.user_can_view_sub_milestones_planning ||
            this.release_data.resources.milestones.accept.trackers.length === 0
        ) {
            return null;
        }

        return (
            "/plugins/agiledashboard/?group_id=" +
            encodeURIComponent(this.project_id) +
            "&planning_id=" +
            encodeURIComponent(this.release_data.planning.id) +
            "&action=show&aid=" +
            encodeURIComponent(this.release_data.id) +
            "&pane=planning-v2"
        );
    }

    get tracker_submilestone_label(): string {
        const submilestone_tracker = this.release_data.resources.milestones.accept.trackers[0];

        if (!submilestone_tracker) {
            return "";
        }
        return submilestone_tracker.label;
    }
}
</script>
