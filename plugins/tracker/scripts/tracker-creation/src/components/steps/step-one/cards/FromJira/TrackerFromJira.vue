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
    <div class="card-content">
        <div
            class="tlp-alert-danger"
            data-test="jira-fail-load-project"
            v-if="error_message.length > 0"
        >
            {{ error_message }}
        </div>
        <tracker-from-jira-server v-if="should_display_connection" v-bind:value="credentials" />
        <button
            type="button"
            class="tlp-button-primary create-from-jira-button"
            data-test="create-from-jira"
            v-on:click="checkConnexionIsReady"
            v-bind:disabled="should_be_disabled"
            v-if="should_display_connection"
        >
            <span v-translate>Connect</span>
            <i v-bind:class="icon_class"></i>
        </button>
        <tracker-from-jira-project v-else-if="project_list" v-bind:project_list="project_list" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Action, Mutation, State } from "vuex-class";
import { Component } from "vue-property-decorator";
import { Credentials, JiraImportData, ProjectList } from "../../../../../store/type";
import TrackerFromJiraProject from "./TrackerFromJiraProject.vue";
import TrackerFromJiraServer from "./TrackerFromJiraServer.vue";

@Component({
    components: { TrackerFromJiraServer, TrackerFromJiraProject },
})
export default class TrackerFromJira extends Vue {
    @State
    readonly from_jira_data!: JiraImportData;

    @Mutation
    readonly setJiraCredentials!: (credentials: Credentials) => void;

    @Mutation
    readonly setProjectList!: (project_list: ProjectList[]) => void;

    @Action
    readonly getJiraProjectList!: (credentials: Credentials) => Promise<ProjectList[]>;

    private is_connection_valid = false;
    private is_loading = false;
    private error_message = "";

    private project_list: ProjectList[] | null = null;

    private credentials: Credentials = {
        server_url: "",
        user_email: "",
        token: "",
    };

    beforeMount(): void {
        if (!this.from_jira_data.project_list) {
            return;
        }

        this.project_list = this.from_jira_data.project_list;
    }

    async checkConnexionIsReady(): Promise<void> {
        this.error_message = "";

        try {
            this.is_loading = true;
            this.project_list = await this.getJiraProjectList(this.credentials);

            this.setJiraCredentials(this.credentials);
            this.setProjectList(this.project_list);

            this.is_connection_valid = true;
        } catch (e) {
            const { error } = await e.response.json();
            this.error_message = error;
        } finally {
            this.is_loading = false;
        }
    }

    get should_display_connection(): boolean {
        return !this.is_connection_valid && !this.project_list;
    }

    get icon_class(): string {
        if (this.is_loading) {
            return "fa tlp-button-icon-right fa-spinner";
        }

        return "fa tlp-button-icon-right fa-connect";
    }

    get should_be_disabled(): boolean {
        return (
            this.credentials.server_url === "" ||
            this.credentials.user_email === "" ||
            this.credentials.token === "" ||
            this.is_loading
        );
    }
}
</script>
