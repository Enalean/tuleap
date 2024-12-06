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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <div class="tlp-modal" role="dialog" aria-labelledby="my-modal-label" ref="modal_element">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="my-modal-label">
                <i
                    class="far fa-fw fa-times-circle tlp-dropdown-menu-item-icon"
                    aria-hidden="true"
                ></i>
                {{ $gettext("Create GitLab branch prefix") }}
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
        <div
            class="tlp-modal-feedback"
            v-if="have_any_rest_error"
            data-test="create-branch-prefix-fail"
        >
            <div class="tlp-alert-danger">
                {{ message_error_rest }}
            </div>
        </div>
        <div class="tlp-modal-body">
            <p>
                {{
                    $gettext(
                        "If set, this prefix will be automatically added to the branch name in the create GitLab branch action",
                    )
                }}
            </p>
            <div class="tlp-form-element">
                <label class="tlp-label" for="create_branch_prefix_input">
                    {{ $gettext("Prefix of the branch name") }}
                </label>
                <input
                    type="text"
                    id="create_branch_prefix_input"
                    class="tlp-input"
                    v-model="create_branch_prefix"
                />
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-on:click="updateCreateBranchPrefix"
                v-bind:disabled="disabled_button"
                data-test="create-branch-prefix-modal-save-button"
            >
                <i
                    v-if="is_updating_gitlab_repository"
                    class="fas fa-spin fa-circle-notch tlp-button-icon"
                    data-test="create-branch-prefix-modal-icon-spin"
                ></i>
                {{ $gettext("Save prefix") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { handleError } from "../../../gitlab/gitlab-error-handler";
import {
    useMutations,
    useNamespacedActions,
    useNamespacedMutations,
    useNamespacedState,
} from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";

const gettext_provider = useGettext();

const { updateGitlabRepositoryCreateBranchPrefix } = useNamespacedActions("gitlab", [
    "updateGitlabRepositoryCreateBranchPrefix",
]);
const { create_branch_prefix_repository } = useNamespacedState("gitlab", [
    "create_branch_prefix_repository",
]);
const { setCreateBranchPrefixModal } = useNamespacedMutations("gitlab", [
    "setCreateBranchPrefixModal",
]);
const { setSuccessMessage } = useMutations(["setSuccessMessage"]);

const modal = ref<Modal | null>(null);
const is_updating_gitlab_repository = ref(false);
const create_branch_prefix = ref("");
const message_error_rest = ref("");

const modal_element = ref();

onMounted((): void => {
    modal.value = createModal(modal_element.value);
    modal.value.addEventListener("tlp-modal-shown", onShownModal);
    modal.value.addEventListener("tlp-modal-hidden", reset);
    setCreateBranchPrefixModal(modal.value);
});

function onShownModal(): void {
    create_branch_prefix.value = create_branch_prefix_repository.value.create_branch_prefix;
}

function reset(): void {
    is_updating_gitlab_repository.value = false;
    create_branch_prefix.value = "";
    message_error_rest.value = "";
}

const have_any_rest_error = computed((): boolean => message_error_rest.value.length > 0);

const disabled_button = computed(
    (): boolean => is_updating_gitlab_repository.value || have_any_rest_error.value,
);

const close_label = gettext_provider.$gettext("Close");

const getSuccessMessage = (create_branch_prefix: string): string => {
    if (create_branch_prefix.length === 0) {
        return gettext_provider.interpolate(
            gettext_provider.$gettext(
                "Create branch prefix for integration %{repository} has been successfully cleared.",
            ),
            { repository: create_branch_prefix_repository.value.label },
        );
    }
    return gettext_provider.interpolate(
        gettext_provider.$gettext(
            "Create branch prefix for integration %{repository} has been successfully updated to '%{branch_prefix}'!",
        ),
        {
            branch_prefix: create_branch_prefix,
            repository: create_branch_prefix_repository.value.label,
        },
    );
};

const updateCreateBranchPrefix = async (event: Event): Promise<void> => {
    event.preventDefault();

    try {
        is_updating_gitlab_repository.value = true;

        const updated_integration = await updateGitlabRepositoryCreateBranchPrefix({
            integration_id: Number(create_branch_prefix_repository.value.integration_id),
            create_branch_prefix: create_branch_prefix.value,
        });

        if (updated_integration) {
            modal.value?.hide();
        }

        const success_message = getSuccessMessage(updated_integration.create_branch_prefix);
        setSuccessMessage(success_message);
    } catch (rest_error) {
        message_error_rest.value = await handleError(rest_error, gettext_provider);
        throw rest_error;
    } finally {
        is_updating_gitlab_repository.value = false;
    }
};

defineExpose({ message_error_rest, is_updating_gitlab_repository, create_branch_prefix });
</script>
