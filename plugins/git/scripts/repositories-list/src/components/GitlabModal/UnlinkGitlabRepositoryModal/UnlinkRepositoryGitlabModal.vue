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
        aria-labelledby="unlink-gitlab-repository-modal-title"
        id="unlink-gitlab-repository-modal"
        class="tlp-modal tlp-modal-danger"
        ref="modal_element"
        data-test="unlink-gitlab-repository-modal-form"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="create-repository-modal-title">
                {{ $gettext("Unlink GitLab repository?") }}
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
        <div class="tlp-modal-body">
            <div
                class="tlp-alert-danger"
                data-test="gitlab-fail-delete-repository"
                v-if="have_any_rest_error"
            >
                {{ message_error_rest }}
            </div>
            <div v-else-if="repository !== null" class="git-repository-create-modal-body">
                <p data-test="confirm-unlink-gitlab-message">
                    {{ confirmation_message }}
                </p>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                data-test="gitlab-unlink-cancel"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-danger tlp-modal-action"
                data-test="button-delete-gitlab-repository"
                v-on:click="confirmUnlink"
                v-bind:disabled="disabled_button"
            >
                <i
                    class="fas tlp-button-icon"
                    v-bind:class="{
                        'fa-spin fa-circle-notch': is_loading,
                        'fa-long-arrow-alt-right': !is_loading,
                    }"
                    data-test="icon-spin"
                ></i>
                {{ $gettext("Unlink the repository") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { deleteIntegrationGitlab } from "../../../gitlab/gitlab-api-querier";
import type { Repository } from "../../../type";
import { useMutations, useNamespacedMutations, useNamespacedState } from "vuex-composition-helpers";
import { computed, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";

const gettext_provider = useGettext();

const { unlink_gitlab_repository } = useNamespacedState("gitlab", ["unlink_gitlab_repository"]);
const { removeRepository, setSuccessMessage } = useMutations([
    "removeRepository",
    "setSuccessMessage",
]);
const { setUnlinkGitlabRepositoryModal } = useNamespacedMutations("gitlab", [
    "setUnlinkGitlabRepositoryModal",
]);

const modal = ref<Modal | null>(null);
const repository = ref<Repository | null>(null);
const message_error_rest = ref("");
const is_loading = ref(false);

const modal_element = ref();

const close_label = gettext_provider.$gettext("Close");

const confirmation_message = computed((): string => {
    if (!repository.value || !repository.value.normalized_path) {
        return "";
    }

    return gettext_provider.interpolate(
        gettext_provider.$gettext(
            "Wow, wait a minute. You are about to unlink the GitLab repository %{ label }. Please confirm your action.",
        ),
        {
            label: repository.value.normalized_path,
        },
    );
});

const have_any_rest_error = computed((): boolean => message_error_rest.value.length > 0);

const disabled_button = computed((): boolean => is_loading.value || have_any_rest_error.value);

const success_message = computed((): string => {
    if (!repository.value || !repository.value.normalized_path) {
        return "";
    }

    return gettext_provider.interpolate(
        gettext_provider.$gettext("GitLab repository %{ label } has been successfully unlinked!"),
        {
            label: repository.value.normalized_path,
        },
    );
});

const onShownModal = (): void => {
    repository.value = unlink_gitlab_repository.value;
};

const reset = (): void => {
    is_loading.value = false;
    message_error_rest.value = "";
};

onMounted((): void => {
    modal.value = createModal(modal_element.value);
    modal.value.addEventListener("tlp-modal-shown", onShownModal);
    modal.value.addEventListener("tlp-modal-hidden", reset);
    setUnlinkGitlabRepositoryModal(modal.value);
});
const handleError = async (rest_error: unknown): Promise<void> => {
    try {
        if (!(rest_error instanceof FetchWrapperError)) {
            throw rest_error;
        }
        const { error } = await rest_error.response.json();
        message_error_rest.value = error.code + " " + error.message;
    } catch (_error) {
        message_error_rest.value = gettext_provider.$gettext("Oops, an error occurred!");
    } finally {
        is_loading.value = false;
    }
};

const confirmUnlink = async (event: Event): Promise<void> => {
    event.preventDefault();

    if (have_any_rest_error.value) {
        return;
    }

    if (!repository.value) {
        return;
    }

    is_loading.value = true;
    try {
        await deleteIntegrationGitlab({
            integration_id: Number(repository.value.integration_id),
        });

        removeRepository(repository.value);
        setSuccessMessage(success_message.value);
        modal.value?.hide();
    } catch (rest_error) {
        await handleError(rest_error);
    } finally {
        is_loading.value = false;
    }
};

defineExpose({ repository, confirmation_message, message_error_rest, disabled_button });
</script>
