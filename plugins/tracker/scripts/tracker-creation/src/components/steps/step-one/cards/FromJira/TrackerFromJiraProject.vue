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
                v-on:change="selectProject($event.target.value)"
                data-test="project-list"
            >
                <option value="" v-translate>Choose a project...</option>
                <option
                    v-for="project in project_list"
                    v-bind:value="project.id"
                    v-bind:key="project.id"
                    v-bind:data-test="`project-${project.id}`"
                >
                    {{ project.label }}
                </option>
            </select>
        </div>
        <div class="tlp-form-element" v-if="is_a_project_selected">
            <label class="tlp-label" for="tracker_name">
                <i class="fa fa-spin fa-spinner" v-if="is_loading" />
                <translate>Tracker</translate>
            </label>
            <select
                class="tlp-select"
                id="tracker_name"
                name="tracker_name"
                data-test="tracker-name"
                v-bind:disabled="!is_a_project_selected"
                v-if="!is_loading"
            >
                <option value="" selected v-translate>Choose a tracker...</option>
                <option
                    v-for="tracker in tracker_list"
                    v-bind:value="tracker.id"
                    v-bind:key="tracker.id"
                >
                    {{ tracker.name }}
                </option>
            </select>
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Credentials, JiraImportData, ProjectList, TrackerList } from "../../../../../store/type";
import { Component, Prop } from "vue-property-decorator";
import { Action, State } from "vuex-class";

@Component
export default class TrackerFromJiraProject extends Vue {
    @Prop({ required: true })
    readonly project_list!: ProjectList[];

    @State
    readonly from_jira_data!: JiraImportData;

    @Action
    readonly getJiraTrackerList!: (
        credentials: Credentials,
        project_key: string
    ) => Promise<TrackerList[]>;

    private is_a_project_selected = false;
    private is_loading = false;
    private error_message = "";

    private tracker_list: TrackerList[] = [];

    async selectProject(value: string): Promise<void> {
        this.error_message = "";

        try {
            this.is_a_project_selected = true;
            this.is_loading = true;
            this.tracker_list = await this.$store.dispatch("getJiraTrackerList", {
                credentials: this.from_jira_data.credentials,
                project_key: value,
            });
        } catch (e) {
            const { error } = await e.response.json();
            this.error_message = error;
        } finally {
            this.is_loading = false;
        }
    }
}
</script>
