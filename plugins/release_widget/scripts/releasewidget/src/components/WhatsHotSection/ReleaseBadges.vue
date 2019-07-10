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
    <div class="project-release-infos-badges">
        <a class="project-release-info-badge tlp-badge-primary toggle-sprints" v-bind:href="getTopPlanningLink" data-test="planning-link">
            <i class="fa fa-map-signs tlp-badge-icon"></i>
            <translate v-bind:translate-n="totalSprint" translate-plural="%{ totalSprint } sprints">
                %{ totalSprint } sprint
            </translate>
        </a>
        <div class="project-release-info-badge tlp-badge-primary tlp-badge-outline">
            <translate v-if="capacityExist()" v-bind:translate-params="{capacity: releaseData.capacity}" data-test="capacity-not-empty">
                Capacity: %{capacity}
            </translate>
            <translate v-else data-test="capacity-empty">
                Capacity: N/A
            </translate>
        </div>
        <div class="project-release-info-badge tlp-badge-warning tlp-badge-outline">
            <translate v-if="initialEffortExist()" v-bind:translate-params="{initialEffort: initialEffort}" data-test="initial-effort-not-empty">
                Initial effort: %{initialEffort}
            </translate>
            <translate v-else data-test="initial-effort-empty">
                Initial effort: N/A
            </translate>
        </div>
    </div>
</template>

<script>
export default {
    name: "ReleaseBadges",
    props: {
        releaseData: Object,
        totalSprint: Number,
        initialEffort: Number
    },
    computed: {
        getTopPlanningLink() {
            return (
                "/plugins/agiledashboard/?group_id=" +
                encodeURIComponent(this.$store.state.project_id) +
                "&planning_id=" +
                encodeURIComponent(this.releaseData.planning.id) +
                "&action=show&aid=" +
                encodeURIComponent(this.releaseData.id) +
                "&pane=planning-v2"
            );
        }
    },
    methods: {
        capacityExist() {
            return this.releaseData.capacity && this.releaseData.capacity > 0;
        },
        initialEffortExist() {
            return this.initialEffort && this.initialEffort > 0;
        }
    }
};
</script>
