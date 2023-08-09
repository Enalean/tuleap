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
        class="project-release-closed-sprint-badge project-release-info-badge tlp-badge-secondary tlp-badge-outline"
        v-if="display_closed_badge"
        data-test="total-closed-sprints"
    >
        <i class="fa fa-map-signs tlp-badge-icon"></i>
        {{ closed_sprints_label }}
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { MilestoneData } from "../../../type";
import { getTrackerSubmilestoneLabel } from "../../../helpers/tracker-label-helper";
import { State } from "vuex-class";

@Component
export default class ReleaseBadgesClosedSprints extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @State
    readonly user_can_view_sub_milestones_planning!: boolean;

    get tracker_submilestone_label(): string {
        return getTrackerSubmilestoneLabel(this.release_data);
    }

    get display_closed_badge(): boolean {
        if (
            typeof this.release_data.total_sprint !== "number" ||
            this.tracker_submilestone_label === ""
        ) {
            return false;
        }

        return (
            this.release_data.total_sprint > 0 &&
            typeof this.release_data.total_closed_sprint === "number" &&
            this.user_can_view_sub_milestones_planning
        );
    }

    get closed_sprints_label(): string {
        const closed_sprints = this.release_data.total_closed_sprint ?? 0;
        const translated = this.$ngettext(
            "%{total_closed_sprint} closed %{tracker_label}",
            "%{total_closed_sprint} closed %{tracker_label}",
            closed_sprints
        );

        return this.$gettextInterpolate(translated, {
            total_closed_sprint: this.release_data.total_closed_sprint,
            tracker_label: this.tracker_submilestone_label,
        });
    }
}
</script>
