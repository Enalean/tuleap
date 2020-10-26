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
    <form
        role="dialog"
        aria-labelledby="fetch-gitlab-project-modal-title"
        id="fetch-gitlab-projects-modal"
        class="tlp-modal"
        ref="fetch_modal"
        v-on:submit="fetchProjects"
        data-test="fetch-gitlab-project-modal-form"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="create-repository-modal-title">
                <i class="fa fa-gitlab tlp-modal-title-icon"></i>
                <translate>Integrate a GitLab remote repository</translate>
            </h1>
            <div
                class="tlp-modal-close"
                tabindex="0"
                role="button"
                data-dismiss="modal"
                v-bind:aria-label="close_label"
            >
                &times;
            </div>
        </div>
        <div class="tlp-modal-body git-repository-create-modal-body">
            <div
                class="tlp-alert-danger"
                data-test="gitlab-fail-load-projects"
                v-if="error_message.length > 0"
            >
                {{ error_message }}
            </div>
            <div
                class="tlp-alert-success"
                data-test="gitlab-success-load-projects"
                v-if="success_message.length > 0"
            >
                {{ success_message }}
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="gitlab_server">
                    <translate>GitLab server URL</translate>
                    <i class="fa fa-asterisk"></i>
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
                <label class="tlp-label" for="gitlab_token_user">
                    <translate>GitLab user token</translate>
                    <i class="fa fa-asterisk"></i>
                </label>
                <input
                    type="text"
                    class="tlp-input"
                    id="gitlab_token_user"
                    required
                    v-model="gitlab_token_user"
                    maxlength="255"
                    data-test="add_gitlab_token_user"
                />
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                <translate>Cancel</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="disabled_button"
                data-test="button_add_gitlab_project"
            >
                <i
                    class="fa fa-arrow-right tlp-button-icon"
                    v-bind:class="{ 'fa-spin fa-sync-alt': is_loading }"
                    data-test="icon-spin"
                ></i>
                <translate>Fetch GitLab projects</translate>
            </button>
        </div>
    </form>
</template>

<script>
import { createModal } from "tlp";
import { mapActions } from "vuex";
import { credentialsAreEmpty, serverUrlIsValid } from "../../gitlab/gitlab-credentials-helper";

export default {
    name: "GitlabProjectModal",
    data() {
        return {
            gitlab_server: "",
            gitlab_token_user: "",
            is_loading: false,
            error_message: "",
            success_message: "",
        };
    },
    computed: {
        close_label() {
            return this.$gettext("Close");
        },
        disabled_button() {
            return this.gitlab_server === "" || this.gitlab_token_user === "" || this.is_loading;
        },
    },
    mounted() {
        const create_modal = createModal(this.$refs.fetch_modal);

        create_modal.addEventListener("tlp-modal-hidden", this.reset);

        this.$store.commit("setAddGitlabProjectModal", create_modal);
    },
    methods: {
        ...mapActions(["getGitlabProjectList"]),
        reset() {
            this.gitlab_server = "";
            this.gitlab_token_user = "";
            this.is_loading = false;
            this.resetMessages();
        },
        resetMessages() {
            this.success_message = "";
            this.error_message = "";
        },
        handleError() {
            this.resetMessages();
            this.error_message = this.$gettext(
                "Cannot connect to GitLab server, please check your credentials."
            );
        },
        async fetchProjects(event) {
            event.preventDefault();
            this.resetMessages();
            const credentials = {
                server_url: this.gitlab_server,
                token: this.gitlab_token_user,
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
                await this.getGitlabProjectList(credentials);
                this.success_message = this.$gettext("GitLab projects have been retrieved.");
            } catch (e) {
                this.handleError();
            } finally {
                this.is_loading = false;
            }
        },
    },
};
</script>
