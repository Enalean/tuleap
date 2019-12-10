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
        <release-badges-open-sprints
            v-if="release_data.total_sprint > 0 && tracker_submilestone_exists"
            v-bind:release_data="release_data"
            v-on:onClickOpenSprintsDetails="on_click_open_sprints_details()"
            data-test="badge-sprint"
        />
        <hr
            v-if="open_sprints_details"
            data-test="line-displayed"
            class="milestone-badges-sprints-separator"
        />
        <div class="project-release-badges-capacity-effort">
            <div class="project-release-info-badge tlp-badge-primary tlp-badge-outline">
                <translate
                    v-if="capacity_exists"
                    v-bind:translate-params="{ capacity: release_data.capacity }"
                    data-test="capacity-not-empty"
                >
                    Capacity: %{capacity}
                </translate>
                <translate v-else data-test="capacity-empty">
                    Capacity: N/A
                </translate>
            </div>
            <div class="project-release-info-badge tlp-badge-warning tlp-badge-outline">
                <translate
                    v-if="initial_effort_exists"
                    v-bind:translate-params="{ initialEffort: release_data.initial_effort }"
                    data-test="initial-effort-not-empty"
                >
                    Initial effort: %{initialEffort}
                </translate>
                <translate v-else data-test="initial-effort-empty">
                    Initial effort: N/A
                </translate>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../../type";
import { State } from "vuex-class";
import ReleaseBadgesOpenSprints from "./ReleaseBadgesOpenSprints.vue";
@Component({
    components: { ReleaseBadgesOpenSprints }
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

    get tracker_submilestone_exists(): boolean {
        if (!this.release_data.resources) {
            return false;
        }
        return this.release_data.resources.milestones.accept.trackers.length > 0;
    }
}
</script>
