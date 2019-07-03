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
    <div class="project-release"
         v-bind:class="{ 'project-release-toggle-closed': !is_open }"
    >
        <div class="project-release-toggle" v-on:click="toggleReleaseDetails()">
            <div class="project-release-icon" data-test="project-release-toggle">
                <i class="fa fa-ellipsis-h"></i>
            </div>
            <h1 class="project-release-title">
                <translate v-bind:translate-params="{ release_label: releaseData.label }">
                    Release %{release_label}
                </translate>
            </h1>
            <span class="project-release-date" v-if="startDateExist()">
                {{ formatDate(releaseData.start_date) }}
                <i class="fa fa-long-arrow-right" data-test="display-arrow"></i>
                {{ formatDate(releaseData.end_date) }}
            </span>

        </div>
        <div v-if="is_open" class="project-release-infos" data-test="toggle_open">
            <a class="project-release-info tlp-badge-primary toggle-sprints" v-bind:href="getTopPlanningLink" data-test="planning-link">
                <i class="fa fa-map-signs tlp-badge-icon"></i>
                <translate v-bind:translate-n="total_sprint" translate-plural="%{ total_sprint } sprints">
                    %{ total_sprint } sprint
                </translate>
            </a>

        </div>
    </div>
</template>

<script>
import { formatDateYearMonthDay } from "../../helpers/date-formatters";

export default {
    name: "ReleaseInformationDisplayer",
    props: {
        releaseData: Object
    },
    data() {
        return {
            is_open: false,
            total_sprint: null
        };
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
    mounted() {
        this.setTotalSprints();
    },
    methods: {
        formatDate(date) {
            return formatDateYearMonthDay(date);
        },
        toggleReleaseDetails() {
            this.is_open = !this.is_open;
        },
        async setTotalSprints() {
            this.total_sprint = await this.$store.dispatch(
                "getNumberOfSprints",
                this.releaseData.id
            );
        },
        startDateExist() {
            return this.releaseData.start_date !== null;
        }
    }
};
</script>
