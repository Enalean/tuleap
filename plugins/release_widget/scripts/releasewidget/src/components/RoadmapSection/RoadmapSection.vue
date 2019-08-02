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
  -
  -->

<template>
    <div class="project-release-timeframe">
        <span class="project-release-label">Roadmap</span>
        <div class="project-other-releases">
            <div class="project-release-roadmap-icon">
                <i class="fa fa-angle-double-up"></i>
            </div>
            <a class="releases-link" v-bind:href="backlog_link" data-test="backlog-link">
                <translate v-bind:translate-params="{nb_backlog_items: nb_backlog_items, nb_upcoming_releases: nb_upcoming_releases}">
                    %{nb_backlog_items} items in the backlog, %{nb_upcoming_releases} upcoming releases
                </translate>
            </a>
        </div>
    </div>
</template>

<script lang="ts">
import { mapState } from "vuex";

export default {
    name: "RoadmapSection",
    computed: {
        ...mapState(["nb_backlog_items", "nb_upcoming_releases", "project_id"]),
        backlog_link() {
            return (
                "/plugins/agiledashboard/?action=show-top&group_id=" +
                encodeURIComponent(this.project_id) +
                "&pane=topplanning-v2"
            );
        }
    }
};
</script>
