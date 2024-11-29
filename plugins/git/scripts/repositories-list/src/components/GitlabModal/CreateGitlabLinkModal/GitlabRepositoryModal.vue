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
        ref="modal_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="create-repository-modal-title">
                {{ $gettext("Add GitLab repository") }}
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
            v-bind:server_url="gitlab_server_url"
        />
        <list-repositories-modal
            v-else
            v-bind:repositories="gitlab_projects"
            v-bind:gitlab_api_token="gitlab_api_token"
            v-bind:server_url="gitlab_server_url"
            v-on:to-back-button="clickBackButton"
            v-on:on-success-close-modal="onSuccessCloseModal"
            ref="listRepositories"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useMutations, useNamespacedMutations } from "vuex-composition-helpers";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import type { GitLabCredentialsWithProjects, GitlabProject } from "../../../type";
import CredentialsFormModal from "./CredentialsFormModal.vue";
import ListRepositoriesModal from "./ListRepositoriesModal.vue";

const { setAddGitlabRepositoryModal } = useNamespacedMutations("gitlab", [
    "setAddGitlabRepositoryModal",
]);
const { setSuccessMessage } = useMutations(["setSuccessMessage"]);

const gettext_provider = useGettext();

const gitlab_projects = ref<null | GitlabProject[]>(null);
const back_button_clicked = ref(false);
const modal = ref<null | Modal>(null);
const gitlab_api_token = ref("");
const gitlab_server_url = ref("");
const modal_element = ref();

const credentialsForm = ref<null | InstanceType<typeof CredentialsFormModal>>(null);
const listRepositories = ref<null | InstanceType<typeof ListRepositoriesModal>>(null);

const close_label = computed((): string => gettext_provider.$gettext("Close"));

onMounted(() => {
    modal.value = createModal(modal_element.value);
    modal.value.addEventListener("tlp-modal-hidden", reset);
    setAddGitlabRepositoryModal(modal.value);
});

const clickBackButton = (): void => {
    back_button_clicked.value = true;
    gitlab_projects.value = null;
};

const onGetGitlabRepositories = ({
    projects,
    token,
    server_url,
}: GitLabCredentialsWithProjects): void => {
    back_button_clicked.value = false;
    gitlab_projects.value = projects;
    gitlab_api_token.value = token;
    gitlab_server_url.value = server_url;
};

const onCloseModal = (): void => {
    reset();
    if (modal.value) {
        modal.value.hide();
    }
};

const onSuccessCloseModal = ({ repository }: { repository: GitlabProject }): void => {
    onCloseModal();
    const success_message = gettext_provider.interpolate(
        gettext_provider.$gettext("GitLab repository %{ label } has been successfully integrated!"),
        {
            label: repository.path_with_namespace,
        },
    );
    setSuccessMessage(success_message);
};

function reset(): void {
    if (credentialsForm.value) {
        credentialsForm.value.reset();
    }

    if (listRepositories.value) {
        listRepositories.value.reset();
    }

    gitlab_projects.value = null;
    back_button_clicked.value = false;
    gitlab_api_token.value = "";
    gitlab_server_url.value = "";
}
</script>
