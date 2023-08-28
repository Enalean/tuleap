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
    <div class="card-content">
        <div class="tlp-form-element">
            <label class="tlp-label" for="project-selector" v-translate>Project</label>
            <select
                data-test="project-select"
                v-model="selected_project_model"
                v-on:change="setTrackers"
                class="tlp-select"
                id="project-selector"
            >
                <option v-bind:value="null" disabled v-translate>Choose a project...</option>
                <option
                    v-for="project in available_projects"
                    v-bind:value="project"
                    v-bind:key="project.id"
                    v-bind:selected="
                        selected_project_model !== null && selected_project_model.id === project.id
                    "
                >
                    {{ project.name }}
                </option>
            </select>
        </div>
        <div
            class="tlp-form-element"
            v-bind:class="{
                'tlp-form-element-disabled': selected_project === null,
            }"
        >
            <label class="tlp-label" for="template-selector" v-translate>Template</label>
            <select
                data-test="project-tracker-select"
                v-model="selected_tracker_model"
                v-on:change="setSelectedProjectTrackerTemplate(selected_tracker_model)"
                v-bind:disabled="selected_project === null"
                class="tlp-select"
                id="template-selector"
                name="tracker-id-from-project"
            >
                <option disabled v-bind:value="null" v-translate>Choose a tracker...</option>
                <option
                    v-for="tracker in trackers_of_selected_project"
                    v-bind:value="tracker"
                    v-bind:key="tracker.id"
                    v-bind:selected="
                        selected_tracker_model !== null && selected_tracker_model.id === tracker.id
                    "
                >
                    {{ tracker.name }}
                </option>
            </select>
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import type { ProjectWithTrackers, Tracker } from "../../../../../store/type";
import { State, Mutation } from "vuex-class";

@Component({
    components: {},
})
export default class TrackerFromAnotherProjectSelector extends Vue {
    @State
    readonly trackers_from_other_projects!: ProjectWithTrackers[];

    @State
    readonly selected_project!: ProjectWithTrackers | null;

    @State
    readonly selected_project_tracker_template!: Tracker | null;

    @Mutation
    readonly setSelectedProjectTrackerTemplate!: (tracker: Tracker | null) => void;

    @Mutation
    readonly setSelectedProject!: (project: ProjectWithTrackers) => void;

    selected_project_model: ProjectWithTrackers | null = null;
    trackers_of_selected_project: Tracker[] = [];
    selected_tracker_model: Tracker | null = null;

    mounted() {
        if (this.selected_project_tracker_template && this.selected_project) {
            this.selected_project_model = this.selected_project;
            this.selected_tracker_model = this.selected_project_tracker_template;
            this.trackers_of_selected_project = this.selected_project.trackers;
        }
    }

    get available_projects(): ProjectWithTrackers[] {
        return this.trackers_from_other_projects.sort((a, b) =>
            a.name.localeCompare(b.name, undefined, { numeric: true })
        );
    }

    setTrackers(): void {
        if (this.selected_project_model === null) {
            return;
        }
        this.setSelectedProject(this.selected_project_model);
        this.setSelectedProjectTrackerTemplate(null);
        this.trackers_of_selected_project = this.selected_project_model.trackers;
        this.selected_tracker_model = null;
    }
}
</script>
