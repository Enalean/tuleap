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
        aria-labelledby="fetch-gitlab-project-modal-title"
        id="fetch-gitlab-projects-modal"
        class="tlp-modal"
        ref="fetch_modal"
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
        <credentials-form-modal
            v-if="gitlab_projects === null || back_button_clicked"
            v-on:on-get-projects="getGitlabProject"
            v-on:on-close-modal="onCloseModal"
            ref="credentialsForm"
        />
        <list-projects-modal
            v-else
            v-bind:projects="gitlab_projects"
            v-on:to-back-button="clickBackButton"
            ref="listProjectsModal"
        />
    </div>
</template>

<script>
import { createModal } from "tlp";
import ListProjectsModal from "./ListProjectsModal.vue";
import CredentialsFormModal from "./CredentialsFormModal.vue";

export default {
    name: "GitlabProjectModal",
    components: { CredentialsFormModal, ListProjectsModal },
    data() {
        return {
            gitlab_projects: null,
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
        this.$store.commit("setAddGitlabProjectModal", this.modal);
    },
    methods: {
        clickBackButton() {
            this.back_button_clicked = true;
            this.gitlab_projects = null;
        },
        getGitlabProject(projects) {
            this.back_button_clicked = false;
            this.gitlab_projects = projects;
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
            const listProjectsModal = this.$refs.listProjectsModal;
            if (listProjectsModal) {
                listProjectsModal.reset();
            }
            this.gitlab_projects = null;
            this.back_button_clicked = false;
        },
    },
};
</script>
