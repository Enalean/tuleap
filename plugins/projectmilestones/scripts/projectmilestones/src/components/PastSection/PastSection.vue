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
    <div class="project-release-timeframe">
        <span class="project-release-label" v-if="last_release" v-translate>
            Recently closed
        </span>
        <release-displayer
            v-if="last_release"
            v-bind:key="last_release.id"
            v-bind:release_data="last_release"
            v-bind:is-past-release="true"
        />
        <span class="project-release-label" v-translate>Past</span>
        <div class="project-other-releases">
            <div class="project-release-time-stripe-icon">
                <i class="fa fa-angle-double-down"></i>
            </div>
            <a class="releases-link" v-bind:href="past_release_link" data-test="past-releases-link">
                <translate
                    v-bind:translate-params="{
                        nb_past: nb_past_releases,
                        label_tracker: label_tracker_planning,
                    }"
                    v-bind:translate-n="nb_past_releases"
                    translate-plural="%{nb_past} past %{label_tracker}"
                >
                    %{nb_past} past %{label_tracker}
                </translate>
            </a>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { State } from "vuex-class";
import ReleaseDisplayer from "../WhatsHotSection/ReleaseDisplayer.vue";
import { MilestoneData } from "../../type";

@Component({
    components: {
        ReleaseDisplayer,
    },
})
export default class PastSection extends Vue {
    @State
    readonly project_id!: number;
    @State
    readonly nb_past_releases!: number;
    @State
    readonly last_release!: MilestoneData | null;
    @Prop()
    readonly label_tracker_planning!: string;

    get past_release_link(): string {
        return (
            "/plugins/agiledashboard/?group_id=" +
            encodeURIComponent(this.project_id) +
            "&period=past"
        );
    }
}
</script>
