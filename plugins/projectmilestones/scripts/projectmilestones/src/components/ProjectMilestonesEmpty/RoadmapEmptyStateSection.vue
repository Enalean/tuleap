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
        <span class="project-release-label">{{ $gettext("Roadmap") }}</span>
        <div class="empty-state-pane" data-test="project-milestone-empty-state">
            <s-v-g-project-milestones-empty-state />
            <span class="empty-state-text">{{ empty_state_label }}</span>
            <a
                v-bind:href="backlog_link"
                class="button-backlog-link empty-state-action"
                data-test="backlog-link"
            >
                <button type="button" class="tlp-button-primary">
                    {{ $gettext("Start Planning") }}
                    <i
                        class="tlp-button-icon-right fas fa-long-arrow-alt-right"
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

    get empty_state_label(): string {
        return this.$gettextInterpolate(
            this.$gettext("There is no item nor milestone in the %{ name } backlog yet."),
            { name: this.project_name },
        );
    }
}
</script>
