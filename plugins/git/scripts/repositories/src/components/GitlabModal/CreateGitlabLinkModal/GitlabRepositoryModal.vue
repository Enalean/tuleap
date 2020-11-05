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
        class="tlp-modal"
        ref="fetch_modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="create-repository-modal-title">
                <i class="fa fa-gitlab tlp-modal-title-icon"></i>
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
            v-on:on-get-gitlab-repositories="getGitlabRepository"
            v-on:on-close-modal="onCloseModal"
            ref="credentialsForm"
        />
        <list-repositories-modal
            v-else
            v-bind:repositories="gitlab_repositories"
            v-on:to-back-button="clickBackButton"
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
        getGitlabRepository(repositories) {
            this.back_button_clicked = false;
            this.gitlab_repositories = repositories;
        },
        onCloseModal() {
            this.reset();
            this.modal.hide();
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
        },
    },
};
</script>
