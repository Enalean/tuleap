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
        <release-description-badges-tracker v-bind:release-data="releaseData"/>
        <div class="release-description-row">
            <div class="release-description" v-dompurify-html="releaseData.description"></div>
            <a v-bind:href="get_overview_link" data-test="overview-link">
                <i class="release-description-link-icon fa fa-long-arrow-right"></i>
                <translate> Go to release overview </translate>
            </a>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../../type";
import { State } from "vuex-class";
import ReleaseDescriptionBadgesTracker from "./ReleaseDescriptionBadgesTracker.vue";

@Component({
    components: { ReleaseDescriptionBadgesTracker }
})
export default class ReleaseDescription extends Vue {
    @Prop()
    readonly releaseData!: MilestoneData;
    @State
    readonly project_id!: number;

    get get_overview_link(): string {
        return (
            "/plugins/agiledashboard/?group_id=" +
            encodeURIComponent(this.project_id) +
            "&planning_id=" +
            encodeURIComponent(this.releaseData.planning!.id) +
            "&action=show&aid=" +
            encodeURIComponent(this.releaseData.id) +
            "&pane=details"
        );
    }
}
</script>
