<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
        aria-labelledby="edit-access-token-gitlab-modal-title"
        class="tlp-modal"
        ref="modal_element"
        data-test="edit-access-token-gitlab-modal-form"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="edit-access-token-gitlab-modal-title">
                {{ $gettext("Edit access token") }}
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
        <access-token-form-modal
            v-if="display_form_to_edit && repository"
            v-bind:repository="repository"
            v-bind:gitlab_token="gitlab_new_token"
            v-on:on-close-modal="onCloseModal"
            v-on:on-get-new-token-gitlab="onGetNewToken"
        />
        <confirm-replace-token-modal
            v-if="display_confirmation_message && repository"
            v-bind:repository="repository"
            v-bind:gitlab_new_token="gitlab_new_token"
            v-on:on-back-button="onBackToEditToken"
            v-on:on-success-edit-token="onSuccessEditToken"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import AccessTokenFormModal from "./AccessTokenFormModal.vue";
import ConfirmReplaceTokenModal from "./ConfirmReplaceTokenModal.vue";
import type { Repository } from "../../../type";
import { useMutations, useNamespacedMutations, useNamespacedState } from "vuex-composition-helpers";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const gettext_provider = useGettext();

const { edit_access_token_gitlab_repository } = useNamespacedState("gitlab", [
    "edit_access_token_gitlab_repository",
]);
const { setEditAccessTokenGitlabRepositoryModal } = useNamespacedMutations("gitlab", [
    "setEditAccessTokenGitlabRepositoryModal",
]);
const { setSuccessMessage } = useMutations(["setSuccessMessage"]);

const modal = ref<Modal | null>(null);
const repository = ref<Repository | null>(null);
const gitlab_new_token = ref("");
const on_back_to_edit = ref(false);

const modal_element = ref();

const close_label = gettext_provider.$gettext("Close");

const reset = (): void => {
    repository.value = null;
    gitlab_new_token.value = "";
    on_back_to_edit.value = false;
};

const onShownModal = (): void => {
    repository.value = edit_access_token_gitlab_repository.value;
};

onMounted((): void => {
    modal.value = createModal(modal_element.value);
    modal.value.addEventListener("tlp-modal-shown", onShownModal);
    modal.value.addEventListener("tlp-modal-hidden", reset);
    setEditAccessTokenGitlabRepositoryModal(modal.value);
});

const onCloseModal = (): void => {
    reset();
    modal.value?.hide();
};

const onBackToEditToken = (): void => {
    on_back_to_edit.value = true;
};

const onGetNewToken = ({ token }: { token: string }): void => {
    gitlab_new_token.value = token;
    on_back_to_edit.value = false;
};

const success_message = computed((): string => {
    if (!repository.value || !repository.value.normalized_path) {
        return "";
    }

    return gettext_provider.interpolate(
        gettext_provider.$gettext(
            "Token of GitLab repository %{ label } has been successfully updated.",
        ),
        {
            label: repository.value.normalized_path,
        },
    );
});

const onSuccessEditToken = (): void => {
    setSuccessMessage(success_message.value);
    onCloseModal();
};

const display_form_to_edit = computed(
    (): boolean =>
        repository.value !== null && (gitlab_new_token.value === "" || on_back_to_edit.value),
);

const display_confirmation_message = computed(
    (): boolean =>
        repository.value !== null && gitlab_new_token.value !== "" && !on_back_to_edit.value,
);

defineExpose({ repository, gitlab_new_token });
</script>
