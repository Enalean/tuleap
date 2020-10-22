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
                v-bind:disabled="is_loading"
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

export default {
    name: "GitlabProjectModal",
    data() {
        return {
            gitlab_server: "",
            gitlab_token_user: "",
            is_loading: false,
        };
    },
    computed: {
        close_label() {
            return this.$gettext("Close");
        },
    },
    mounted() {
        const create_modal = createModal(this.$refs.fetch_modal);

        create_modal.addEventListener("tlp-modal-hidden", this.reset);

        this.$store.commit("setAddGitlabProjectModal", create_modal);
    },
    methods: {
        reset() {
            this.gitlab_server = "";
            this.gitlab_token_user = "";
            this.is_loading = false;
        },
        fetchProjects(event) {
            event.preventDefault();
            this.is_loading = true;
        },
    },
};
</script>
