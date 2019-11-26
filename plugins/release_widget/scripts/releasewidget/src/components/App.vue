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
    <section>
        <div v-if="is_browser_IE11" class="tlp-alert-danger" data-test="is-IE11" v-translate>
            The plugin "Release Widget" is not supported under IE11. Please use a more recent browser.
        </div>
        <div v-else-if="has_rest_error" class="tlp-alert-danger" data-test="show-error-message">
            {{ error }}
        </div>
        <div v-else-if="is_loading" class="release-loader" data-test="is-loading"></div>
        <div v-else>
            <div class="project-release-widget-content" data-test="widget-content">
                <roadmap-section v-bind:label_tracker_planning="label_tracker_planning"/>
                <whats-hot-section/>
            </div>
        </div>
    </section>
</template>

<script lang="ts">
import WhatsHotSection from "./WhatsHotSection/WhatsHotSection.vue";
import RoadmapSection from "./RoadmapSection/RoadmapSection.vue";
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Action, Getter, Mutation, State } from "vuex-class";
import { TrackerAgileDashboard } from "../type";

@Component({
    components: { WhatsHotSection, RoadmapSection }
})
export default class App extends Vue {
    @Prop()
    readonly label_tracker_planning!: string;
    @Prop()
    readonly project_id!: number;
    @Prop()
    readonly is_browser_IE11!: boolean;
    @Prop()
    readonly nb_upcoming_releases!: number;
    @Prop()
    readonly nb_backlog_items!: number;
    @Prop()
    readonly trackers_agile_dashboard!: TrackerAgileDashboard[];
    @State
    readonly is_loading!: boolean;
    @State
    readonly error_message!: string;
    @Getter
    has_rest_error!: boolean;
    @Mutation
    setProjectId!: (projectId: number) => void;
    @Mutation
    setNbUpcomingReleases!: (nbUpcomingReleases: number) => void;
    @Mutation
    setNbBacklogItem!: (nbBacklogItems: number) => void;
    @Mutation
    setTrackers!: (trackers_id: TrackerAgileDashboard[]) => void;
    @Action
    getMilestones!: () => void;

    created(): void {
        this.setProjectId(this.project_id);
        this.setNbUpcomingReleases(this.nb_upcoming_releases);
        this.setNbBacklogItem(this.nb_backlog_items);
        this.setTrackers(this.trackers_agile_dashboard);
        this.getMilestones();
    }

    get error(): string {
        return this.error_message === ""
            ? this.$gettext("Oops, an error occurred!")
            : this.error_message;
    }
}
</script>
