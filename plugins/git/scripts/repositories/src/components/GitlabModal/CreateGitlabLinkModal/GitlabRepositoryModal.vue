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
        ref="fetch_modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="create-repository-modal-title">
                <i class="fab fa-gitlab tlp-modal-title-icon"></i>
                <translate>Add GitLab repository</translate>
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
        <credentials-form-modal
            v-if="gitlab_repositories === null || back_button_clicked"
            v-on:on-get-gitlab-repositories="getGitlabRepositories"
            v-on:on-close-modal="onCloseModal"
            ref="credentialsForm"
            v-bind:gitlab_api_token="gitlab_api_token"
            v-bind:server_url="server_url"
        />
        <list-repositories-modal
            v-else
            v-bind:repositories="gitlab_repositories"
            v-bind:gitlab_api_token="gitlab_api_token"
            v-bind:server_url="server_url"
            v-on:to-back-button="clickBackButton"
            v-on:on-success-close-modal="onSuccessCloseModal"
            ref="listRepositoriesModal"
        />
    </div>
</template>

<script>
import { createModal } from "tlp";
import ListRepositoriesModal from "./ListRepositoriesModal.vue";
import CredentialsFormModal from "./CredentialsFormModal.vue";

export default {
    name: "GitlabRepositoryModal",
    components: { CredentialsFormModal, ListRepositoriesModal },
    data() {
        return {
            gitlab_repositories: null,
            back_button_clicked: false,
            modal: null,
            gitlab_api_token: "",
            server_url: "",
        };
    },
    computed: {
        close_label() {
            return this.$gettext("Close");
        },
    },
    mounted() {
        this.modal = createModal(this.$refs.fetch_modal);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.$store.commit("setAddGitlabRepositoryModal", this.modal);
    },
    methods: {
        clickBackButton() {
            this.back_button_clicked = true;
            this.gitlab_repositories = null;
        },
        getGitlabRepositories({ repositories, token, server_url }) {
            this.back_button_clicked = false;
            this.gitlab_repositories = repositories;
            this.gitlab_api_token = token;
            this.server_url = server_url;
        },
        onCloseModal() {
            this.reset();
            this.modal.hide();
        },
        onSuccessCloseModal({ repository }) {
            this.onCloseModal();
            const success_message = this.$gettextInterpolate(
                this.$gettext("GitLab repository %{ label } has been successfully integrated!"),
                {
                    label: repository.path_with_namespace,
                }
            );
            this.$store.commit("setSuccessMessage", success_message);
        },
        reset() {
            const credentialsForm = this.$refs.credentialsForm;
            if (credentialsForm) {
                credentialsForm.reset();
            }
            const listRepositoriesModal = this.$refs.listRepositoriesModal;
            if (listRepositoriesModal) {
                listRepositoriesModal.reset();
            }
            this.gitlab_repositories = null;
            this.back_button_clicked = false;
            this.gitlab_api_token = "";
            this.server_url = "";
        },
    },
};
</script>
