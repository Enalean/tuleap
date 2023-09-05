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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -->

<template>
    <form v-on:submit="fetchRepositories" data-test="fetch-gitlab-repository-modal-form">
        <div class="tlp-modal-body git-repository-create-modal-body">
            <div
                class="tlp-alert-danger"
                data-test="gitlab-fail-load-repositories"
                v-if="error_message.length > 0"
            >
                {{ error_message }}
            </div>
            <div
                class="tlp-alert-warning"
                data-test="gitlab-empty-repositories"
                v-if="empty_message.length > 0"
            >
                {{ empty_message }}
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="gitlab_server">
                    <translate>GitLab server URL</translate>
                    <i class="fas fa-asterisk" aria-hidden="true"></i>
                </label>
                <input
                    type="url"
                    class="tlp-input"
                    id="gitlab_server"
                    required
                    v-model="gitlab_server"
                    placeholder="https://example.com"
                    pattern="https://.+"
                    maxlength="255"
                    data-test="add_gitlab_server"
                />
            </div>

            <div class="tlp-form-element">
                <label class="tlp-label" for="gitlab_project_token">
                    <translate>GitLab access token (personal or project)</translate>
                    <i class="fas fa-asterisk" aria-hidden="true"></i>
                </label>
                <input
                    type="password"
                    class="tlp-input"
                    id="gitlab_project_token"
                    required
                    v-model="gitlab_token"
                    maxlength="255"
                    data-test="add_gitlab_project_token"
                    autocomplete="off"
                />
                <p class="tlp-text-info gitlab-test-info-form-token-modal">
                    <translate>
                        The access token will be used to fetch repositories, configure project hooks
                        and automatically write comments on GitLab commit and merge requests.
                    </translate>
                </p>
                <p class="tlp-text-info">
                    <translate>GitLab access token scope must contain at least: api.</translate>
                </p>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-on:click="$emit('on-close-modal')"
            >
                <translate>Cancel</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="disabled_button"
                data-test="button-add-gitlab-repository"
            >
                <i
                    class="fas tlp-button-icon"
                    v-bind:class="{
                        'fa-spin fa-circle-notch': is_loading,
                        'fa-long-arrow-alt-right': !is_loading,
                    }"
                    data-test="icon-spin"
                ></i>
                <translate>Fetch GitLab repositories</translate>
            </button>
        </div>
    </form>
</template>

<script lang="ts">
import { credentialsAreEmpty, serverUrlIsValid } from "../../../gitlab/gitlab-credentials-helper";
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import type { GitLabCredentials, GitlabProject } from "../../../type";
import { namespace } from "vuex-class";

const gitlab = namespace("gitlab");

@Component
export default class CredentialsFormModal extends Vue {
    @Prop({ required: true })
    readonly gitlab_api_token!: string;
    @Prop({ required: true })
    readonly server_url!: string;

    @gitlab.Action
    readonly getGitlabProjectList!: (credentials: GitLabCredentials) => Promise<GitlabProject[]>;

    gitlab_server = this.server_url;
    gitlab_token = this.gitlab_api_token;
    is_loading = false;
    error_message = "";
    empty_message = "";
    private gitlab_projects: null | GitlabProject[] = null;

    get disabled_button(): boolean {
        return this.gitlab_server === "" || this.gitlab_token === "" || this.is_loading;
    }

    reset(): void {
        this.gitlab_server = "";
        this.gitlab_token = "";
        this.is_loading = false;
        this.gitlab_projects = null;
        this.resetMessages();
    }

    resetMessages(): void {
        this.error_message = "";
        this.empty_message = "";
    }

    handleError(): void {
        this.resetMessages();
        this.error_message = this.$gettext(
            "Cannot connect to GitLab server, please check your credentials.",
        );
    }

    async fetchRepositories(event: Event): Promise<void> {
        event.preventDefault();
        this.resetMessages();

        const credentials = {
            server_url: this.gitlab_server,
            token: this.gitlab_token,
        };

        if (credentialsAreEmpty(credentials)) {
            this.error_message = this.$gettext(
                "You must provide a valid GitLab server and user API token",
            );
            return;
        }

        if (!serverUrlIsValid(credentials.server_url)) {
            this.error_message = this.$gettext("Server url is invalid");
            return;
        }

        try {
            this.is_loading = true;
            this.gitlab_projects = await this.getGitlabProjectList(credentials);

            if (this.gitlab_projects.length === 0) {
                this.empty_message = this.$gettext(
                    "No repository is available with your GitLab account",
                );
                return;
            }

            this.$emit("on-get-gitlab-repositories", {
                projects: this.gitlab_projects,
                token: this.gitlab_token,
                server_url: this.gitlab_server,
            });
        } catch (e) {
            this.handleError();
        } finally {
            this.is_loading = false;
        }
    }
}
</script>
