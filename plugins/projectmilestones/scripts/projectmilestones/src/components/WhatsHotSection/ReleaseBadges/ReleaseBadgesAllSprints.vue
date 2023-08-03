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
    <div
        class="project-release-open-sprint-badges"
        v-on:click="$emit('on-click-open-sprints-details')"
        v-if="display_sprint_badge"
    >
        <i class="project-release-open-sprint-badge-icon-toggle fa" />
        <div
            class="project-release-info-badge project-release-info-badge-open-sprint tlp-badge-primary"
            v-bind:class="{ 'tlp-badge-outline': isPastRelease }"
            data-test="badge-sprint"
        >
            <i class="fa fa-map-signs tlp-badge-icon" />
            {{ release_data.total_sprint }} {{ tracker_submilestone_label }}
        </div>
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import type { MilestoneData } from "../../../type";
import { getTrackerSubmilestoneLabel } from "../../../helpers/tracker-label-helper";
import { useStore } from "../../../stores/root";

@Component
export default class ReleaseBadgesAllSprints extends Vue {
    public root_store = useStore();
    @Prop()
    readonly release_data!: MilestoneData;
    @Prop()
    readonly isPastRelease!: boolean;

    get tracker_submilestone_label(): string {
        return getTrackerSubmilestoneLabel(this.release_data);
    }

    get display_sprint_badge(): boolean {
        return (
            this.tracker_submilestone_label !== "" &&
            this.root_store.user_can_view_sub_milestones_planning
        );
    }
}
</script>
