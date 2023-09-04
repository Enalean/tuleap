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
    <div
        role="dialog"
        aria-labelledby="fetch-gitlab-repository-modal-title"
        id="fetch-gitlab-repositories-modal"
        class="tlp-modal fetch-gitlab-repositories-modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="create-repository-modal-title">
                <translate>Add GitLab repository</translate>
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close_label"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <credentials-form-modal
            v-if="gitlab_projects === null || back_button_clicked"
            v-on:on-get-gitlab-repositories="onGetGitlabRepositories"
            v-on:on-close-modal="onCloseModal"
            ref="credentialsForm"
            v-bind:gitlab_api_token="gitlab_api_token"
            v-bind:server_url="server_url"
        />
        <list-repositories-modal
            v-else
            v-bind:repositories="gitlab_projects"
            v-bind:gitlab_api_token="gitlab_api_token"
            v-bind:server_url="server_url"
            v-on:to-back-button="clickBackButton"
            v-on:on-success-close-modal="onSuccessCloseModal"
            ref="listRepositoriesModal"
        />
    </div>
</template>

<script lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import ListRepositoriesModal from "./ListRepositoriesModal.vue";
import CredentialsFormModal from "./CredentialsFormModal.vue";
import { Component } from "vue-property-decorator";
import Vue from "vue";
import type { GitLabCredentialsWithProjects, GitlabProject } from "../../../type";

@Component({ components: { CredentialsFormModal, ListRepositoriesModal } })
export default class GitlabRepositoryModal extends Vue {
    gitlab_projects: null | GitlabProject[] = null;
    back_button_clicked = false;
    private modal: null | Modal = null;
    gitlab_api_token = "";
    server_url = "";

    get close_label(): string {
        return this.$gettext("Close");
    }

    mounted(): void {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.$store.commit("gitlab/setAddGitlabRepositoryModal", this.modal);
    }

    clickBackButton(): void {
        this.back_button_clicked = true;
        this.gitlab_projects = null;
    }

    onGetGitlabRepositories({ projects, token, server_url }: GitLabCredentialsWithProjects): void {
        this.back_button_clicked = false;
        this.gitlab_projects = projects;
        this.gitlab_api_token = token;
        this.server_url = server_url;
    }

    onCloseModal(): void {
        this.reset();
        if (this.modal) {
            this.modal.hide();
        }
    }

    onSuccessCloseModal({ repository }: { repository: GitlabProject }): void {
        this.onCloseModal();
        const success_message = this.$gettextInterpolate(
            this.$gettext("GitLab repository %{ label } has been successfully integrated!"),
            {
                label: repository.path_with_namespace,
            },
        );
        this.$store.commit("setSuccessMessage", success_message);
    }

    reset(): void {
        const credentialsForm = this.$refs.credentialsForm;
        if (credentialsForm instanceof CredentialsFormModal) {
            credentialsForm.reset();
        }
        const listRepositoriesModal = this.$refs.listRepositoriesModal;
        if (listRepositoriesModal instanceof ListRepositoriesModal) {
            listRepositoriesModal.reset();
        }
        this.gitlab_projects = null;
        this.back_button_clicked = false;
        this.gitlab_api_token = "";
        this.server_url = "";
    }
}
</script>
