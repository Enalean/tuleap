<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div>
        <div
            class="tlp-alert-danger"
            data-test="jira-fail-load-trackers"
            v-if="error_message.length > 0"
        >
            {{ error_message }}
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="project" v-translate>Project</label>
            <select
                class="tlp-select"
                id="project"
                name="project"
                v-on:change="selectProject($event)"
                data-test="project-list"
            >
                <option value="" v-translate>Choose a project...</option>
                <option
                    v-for="project in project_list"
                    v-bind:value="JSON.stringify(project)"
                    v-bind:key="project.id"
                    v-bind:data-test="`project-${project.id}`"
                    v-bind:selected="
                        from_jira_data.project !== null && project.id === from_jira_data.project.id
                    "
                >
                    {{ project.label }}
                </option>
            </select>
        </div>
        <div
            class="tlp-form-element"
            v-if="is_a_project_selected || from_jira_data.tracker_list !== null"
        >
            <label class="tlp-label" for="tracker_name">
                <i class="fa fa-spin fa-spinner" v-if="is_loading" />
                <translate>Issue type</translate>
            </label>
            <select
                class="tlp-select"
                id="tracker_name"
                name="tracker_name"
                data-test="tracker-name"
                v-if="!is_loading"
                v-on:change="selectTracker($event)"
            >
                <option value="" selected v-translate>Choose an issue type...</option>
                <option
                    v-for="tracker in from_jira_data.tracker_list"
                    v-bind:value="JSON.stringify(tracker)"
                    v-bind:key="tracker.id"
                    v-bind:selected="
                        from_jira_data.tracker_list !== null &&
                        from_jira_data.tracker !== null &&
                        tracker.id === from_jira_data.tracker.id
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
import type {
    Credentials,
    JiraImportData,
    ProjectList,
    TrackerList,
} from "../../../../../store/type";
import { Component, Prop } from "vue-property-decorator";
import { Action, Mutation, State } from "vuex-class";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

@Component
export default class TrackerFromJiraProject extends Vue {
    @Prop({ required: true })
    readonly project_list!: ProjectList[];

    @State
    readonly from_jira_data!: JiraImportData;

    @Mutation
    readonly setTrackerList!: (tracker_list: TrackerList[]) => void;

    @Mutation
    readonly setProject!: (project: ProjectList) => void;

    @Mutation
    readonly setTrackerName!: (name: string) => void;

    @Mutation
    readonly setTracker!: (tracker: TrackerList) => void;

    @Action
    readonly getJiraTrackerList!: (
        credentials: Credentials,
        project_key: string
    ) => Promise<TrackerList[]>;

    is_a_project_selected = false;
    is_loading = false;
    error_message = "";

    async selectProject(event: Event): Promise<void> {
        if (!(event.target instanceof HTMLSelectElement)) {
            return;
        }
        this.error_message = "";

        const project = JSON.parse(event.target.value);
        try {
            this.is_a_project_selected = true;
            this.is_loading = true;
            const tracker_list = await this.$store.dispatch("getJiraTrackerList", {
                credentials: this.from_jira_data.credentials,
                project_key: project.id,
            });

            this.setTrackerList(tracker_list);
            this.setProject(project);
        } catch (e) {
            if (!(e instanceof FetchWrapperError)) {
                throw e;
            }
            const { error } = await e.response.json();
            this.error_message = error;
        } finally {
            this.is_loading = false;
        }
    }

    selectTracker(event: Event): void {
        if (!(event.target instanceof HTMLSelectElement)) {
            return;
        }
        const tracker = JSON.parse(event.target.value);
        this.setTrackerName(tracker.name);
        this.setTracker(tracker);
    }
}
</script>
