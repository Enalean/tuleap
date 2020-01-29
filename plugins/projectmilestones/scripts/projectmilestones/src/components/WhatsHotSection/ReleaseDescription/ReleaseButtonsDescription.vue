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
    <div class="release-description-link">
        <a v-if="get_overview_link" v-bind:href="get_overview_link" data-test="overview-link">
            <i class="release-description-link-icon fa fa-bar-chart" />
            <translate>
                Overview
            </translate>
        </a>
        <a
            v-if="get_planning_link"
            v-bind:href="get_planning_link"
            data-test="planning-link"
            class="release-planning-link"
        >
            <i class="release-description-link-icon fa fa-sign-in" />
            <translate v-bind:translate-params="{ label_submilestone: tracker_submilestone_label }">
                %{label_submilestone} Planning
            </translate>
        </a>
        <a
            v-if="get_cardwall_link"
            v-bind:href="get_cardwall_link"
            data-test="cardwall-link"
            class="release-planning-link"
        >
            <i class="release-description-link-icon fa fa-table" />
            <translate>
                Cardwall
            </translate>
        </a>
        <a
            v-if="get_taskboard_pane"
            v-bind:href="get_taskboard_pane.uri"
            data-test="taskboard-link"
            class="release-planning-link"
        >
            <i
                class="release-description-link-icon fa"
                data-test="taskboard-icon"
                v-bind:class="get_taskboard_pane.icon_name"
            />
            {{ get_taskboard_pane.title }}
        </a>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData, Pane } from "../../../type";
import { State } from "vuex-class";

@Component
export default class ReleaseButtonsDescription extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @State
    readonly project_id!: number;
    @State
    readonly label_tracker_planning!: string;
    @State
    readonly user_can_view_sub_milestones_planning!: boolean;

    get get_overview_link(): string | null {
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

    get get_planning_link(): string | null {
        if (!this.user_can_view_sub_milestones_planning) {
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

    get get_cardwall_link(): string | null {
        if (!this.release_data.resources.cardwall) {
            return null;
        }

        return (
            "/plugins/agiledashboard/?group_id=" +
            encodeURIComponent(this.project_id) +
            "&planning_id=" +
            encodeURIComponent(this.release_data.planning.id) +
            "&action=show&aid=" +
            encodeURIComponent(this.release_data.id) +
            "&pane=cardwall"
        );
    }

    get tracker_submilestone_label(): string {
        const submilestone_tracker = this.release_data.resources.milestones.accept.trackers[0];

        if (!submilestone_tracker) {
            return "";
        }
        return submilestone_tracker.label;
    }

    get get_taskboard_pane(): undefined | Pane {
        return this.release_data.resources.additional_panes.find(
            pane => pane.identifier === "taskboard"
        );
    }
}
</script>
