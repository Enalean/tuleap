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
    <div class="project-release-badges-capacity-effort">
        <div class="project-release-info-badge tlp-badge-primary tlp-badge-outline">
            <translate
                v-if="capacity_exists"
                v-bind:translate-params="{ capacity: release_data.capacity }"
                data-test="capacity-not-empty"
            >
                Capacity: %{capacity}
            </translate>
            <translate v-else data-test="capacity-empty">Capacity: N/A</translate>
        </div>
        <div
            class="project-release-info-badge tlp-badge-outline"
            v-bind:class="initial_effort_badge_class"
            data-test="initial_effort_badge"
        >
            <translate
                v-if="initial_effort_exists"
                v-bind:translate-params="{ initialEffort: release_data.initial_effort }"
                data-test="initial-effort-not-empty"
            >
                Initial effort: %{initialEffort}
            </translate>
            <translate v-else data-test="initial-effort-empty">Initial effort: N/A</translate>
        </div>
        <release-buttons-description v-bind:release_data="release_data">
            <a
                v-if="get_planning_link"
                v-bind:href="get_planning_link"
                data-test="planning-link"
                class="release-planning-link release-planning-link-item tlp-tooltip tlp-tooltip-top"
                v-bind:data-tlp-tooltip="release_planning_link_label"
                v-bind:aria-label="release_planning_link_label"
            >
                <i class="release-description-link-icon fas fa-sign-in-alt" aria-hidden="true"></i>
            </a>
        </release-buttons-description>
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import { MilestoneData } from "../../../type";
import ReleaseButtonsDescription from "../ReleaseDescription/ReleaseButtonsDescription.vue";
import { State } from "vuex-class";

@Component({
    components: { ReleaseButtonsDescription },
})
export default class ReleaseOthersBadges extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @State
    readonly user_can_view_sub_milestones_planning!: boolean;
    @State
    readonly project_id!: number;

    get capacity_exists(): boolean {
        if (!this.release_data.capacity) {
            return false;
        }
        return this.release_data.capacity > 0;
    }

    get initial_effort_exists(): boolean {
        if (!this.release_data.initial_effort) {
            return false;
        }
        return this.release_data.initial_effort > 0;
    }

    get initial_effort_badge_class(): string {
        if (
            this.release_data.capacity &&
            this.release_data.initial_effort &&
            this.release_data.capacity < this.release_data.initial_effort
        ) {
            return "tlp-badge-warning";
        }

        return "tlp-badge-primary";
    }

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

    get release_planning_link_label(): string {
        const submilestone_tracker = this.release_data.resources.milestones.accept.trackers[0];
        let label = submilestone_tracker.label;

        if (!submilestone_tracker) {
            label = "";
        }

        return this.$gettextInterpolate(this.$gettext("%{label_submilestone} Planning"), {
            label_submilestone: label,
        });
    }
}
</script>
