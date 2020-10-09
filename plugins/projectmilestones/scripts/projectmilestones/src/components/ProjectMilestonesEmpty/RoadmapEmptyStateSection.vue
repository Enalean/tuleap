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
    <div class="project-release-timeframe">
        <translate class="project-release-label">Roadmap</translate>
        <div class="empty-state-container">
            <s-v-g-project-milestones-empty-state />
            <translate class="empty-state-text" v-bind:translate-params="{ name: project_name }">
                There is no item nor milestone in the %{ name } backlog yet.
            </translate>
            <a v-bind:href="backlog_link" class="button-backlog-link" data-test="backlog-link">
                <button type="button" class="tlp-button-primary empty-state-button">
                    <translate>Start Planning</translate>
                    <i
                        class="fas fa-long-arrow-alt-right empty-state-button-arrow-link"
                        data-test="display-arrow"
                    ></i>
                </button>
            </a>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { State } from "vuex-class";
import SVGProjectMilestonesEmptyState from "./SVGProjectMilestonesEmptyState.vue";
@Component({
    components: { SVGProjectMilestonesEmptyState },
})
export default class RoadmapEmptyStateSection extends Vue {
    @State
    readonly project_name!: string;
    @State
    readonly project_id!: number;

    get backlog_link(): string {
        return (
            "/plugins/agiledashboard/?action=show-top&group_id=" +
            encodeURIComponent(this.project_id) +
            "&pane=topplanning-v2"
        );
    }
}
</script>
