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
        class="project-release-infos-badges"
        v-bind:class="{ 'on-open-sprints-details': open_sprints_details }"
    >
        <release-badges-all-sprints
            v-if="release_data.total_sprint > 0 && tracker_submilestone_exists"
            v-bind:release_data="release_data"
            v-on:onClickOpenSprintsDetails="on_click_open_sprints_details()"
            data-test="badge-sprint"
        />
        <release-badges-closed-sprints
            v-if="open_sprints_details"
            v-bind:release_data="release_data"
        />
        <hr
            v-if="open_sprints_details"
            data-test="line-displayed"
            class="milestone-badges-sprints-separator"
        />
        <release-others-badges v-bind:release_data="release_data" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../../type";
import { State } from "vuex-class";
import ReleaseBadgesAllSprints from "./ReleaseBadgesAllSprints.vue";
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
@Component({
    components: { ReleaseBadgesClosedSprints, ReleaseOthersBadges, ReleaseBadgesAllSprints }
})
export default class ReleaseBadgesDisplayer extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @State
    readonly project_id!: number;

    open_sprints_details = false;

    on_click_open_sprints_details(): void {
        this.open_sprints_details = !this.open_sprints_details;
    }

    get tracker_submilestone_exists(): boolean {
        if (!this.release_data.resources) {
            return false;
        }
        return this.release_data.resources.milestones.accept.trackers.length > 0;
    }
}
</script>
