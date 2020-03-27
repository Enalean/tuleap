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
        <div v-if="has_rest_error" class="tlp-alert-danger" data-test="show-error-message">
            {{ error }}
        </div>
        <div v-else-if="is_loading" class="release-loader" data-test="is-loading"></div>
        <div v-else>
            <div class="project-release-widget-content" data-test="widget-content">
                <roadmap-section v-bind:label_tracker_planning="label_tracker_planning" />
                <whats-hot-section />
                <past-section v-bind:label_tracker_planning="label_tracker_planning" />
            </div>
        </div>
    </section>
</template>

<script lang="ts">
import WhatsHotSection from "./WhatsHotSection/WhatsHotSection.vue";
import RoadmapSection from "./RoadmapSection/RoadmapSection.vue";
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { Action, Getter, State } from "vuex-class";
import { TrackerAgileDashboard } from "../type";
import PastSection from "./PastSection/PastSection.vue";

@Component({
    components: { PastSection, WhatsHotSection, RoadmapSection },
})
export default class App extends Vue {
    @State
    readonly label_tracker_planning!: string;
    @State
    readonly project_id!: number;
    @State
    readonly nb_upcoming_releases!: number;
    @State
    readonly nb_backlog_items!: number;
    @State
    readonly trackers_agile_dashboard!: TrackerAgileDashboard[];
    @State
    readonly is_loading!: boolean;
    @State
    readonly error_message!: string;
    @Getter
    has_rest_error!: boolean;
    @Action
    getMilestones!: () => void;

    created(): void {
        this.getMilestones();
    }

    get error(): string {
        return this.error_message === ""
            ? this.$gettext("Oops, an error occurred!")
            : this.error_message;
    }
}
</script>
