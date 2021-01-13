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
                    <i class="fas fa-asterisk"></i>
                </label>
                <input
                    type="text"
                    class="tlp-input"
                    id="gitlab_server"
                    required
                    v-model="gitlab_server"
                    placeholder="https://example.com"
                    pattern="(https?)://.+"
                    maxlength="255"
                    data-test="add_gitlab_server"
                />
            </div>

            <div class="tlp-form-element">
                <label class="tlp-label" for="gitlab_project_token">
                    <translate>GitLab project token</translate>
                    <i class="fas fa-asterisk"></i>
                </label>
                <input
                    type="text"
                    class="tlp-input"
                    id="gitlab_project_token"
                    required
                    v-model="gitlab_project_token"
                    maxlength="255"
                    data-test="add_gitlab_project_token"
                />
                <p class="tlp-text-info">
                    <i class="fas fa-info-circle"></i>
                    <translate>GitLab project token scope must contain at least: api.</translate>
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

<script>
import { mapActions } from "vuex";
import { credentialsAreEmpty, serverUrlIsValid } from "../../../gitlab/gitlab-credentials-helper";

export default {
    name: "CredentialsFormModal",
    props: {
        user_token: {
            type: String,
            default: () => "",
        },
        server_url: {
            type: String,
            default: () => "",
        },
    },
    data() {
        return {
            gitlab_server: this.server_url,
            gitlab_project_token: this.user_token,
            is_loading: false,
            error_message: "",
            empty_message: "",
            gitlab_repositories: null,
        };
    },
    computed: {
        disabled_button() {
            return this.gitlab_server === "" || this.gitlab_project_token === "" || this.is_loading;
        },
    },
    methods: {
        ...mapActions(["getGitlabRepositoryList"]),
        reset() {
            this.gitlab_server = "";
            this.gitlab_project_token = "";
            this.is_loading = false;
            this.gitlab_repositories = null;
            this.resetMessages();
        },
        resetMessages() {
            this.error_message = "";
            this.empty_message = "";
        },
        handleError() {
            this.resetMessages();
            this.error_message = this.$gettext(
                "Cannot connect to GitLab server, please check your credentials."
            );
        },
        async fetchRepositories(event) {
            event.preventDefault();
            this.resetMessages();

            const credentials = {
                server_url: this.gitlab_server,
                token: this.gitlab_project_token,
            };

            if (credentialsAreEmpty(credentials)) {
                this.error_message = this.$gettext(
                    "You must provide a valid GitLab server and user API token"
                );
                return;
            }

            if (!serverUrlIsValid(credentials.server_url)) {
                this.error_message = this.$gettext("Server url is invalid");
                return;
            }

            try {
                this.is_loading = true;
                this.gitlab_repositories = await this.getGitlabRepositoryList(credentials);

                if (this.gitlab_repositories.length === 0) {
                    this.empty_message = this.$gettext(
                        "No repository is available with your GitLab account"
                    );
                    return;
                }

                this.$emit("on-get-gitlab-repositories", {
                    repositories: this.gitlab_repositories,
                    token: this.gitlab_project_token,
                    server_url: this.gitlab_server,
                });
            } catch (e) {
                this.handleError();
            } finally {
                this.is_loading = false;
            }
        },
    },
};
</script>
