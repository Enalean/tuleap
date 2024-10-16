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
                {{ $gettext("Artifact closure") }}
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
            data-test="update-integration-fail"
        >
            <div class="tlp-alert-danger">
                {{ message_error_rest }}
            </div>
        </div>
        <div class="tlp-modal-body">
            <div class="tlp-form-element">
                <label class="tlp-label tlp-checkbox">
                    <input type="checkbox" v-model="allow_artifact_closure" />
                    {{ $gettext("Allow artifact closure") }}
                </label>
                <p class="tlp-text-info">
                    {{
                        $gettext(
                            "If selected, artifacts of this project can be closed with GitLab commit messages from the selected repository.",
                        )
                    }}
                </p>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-on:click="updateArtifactClosureValue"
                v-bind:disabled="disabled_button"
                data-test="update-artifact-closure-modal-save-button"
            >
                <i
                    v-if="is_updating_gitlab_repository"
                    class="fas fa-spin fa-circle-notch tlp-button-icon"
                    data-test="update-artifact-closure-modal-icon-spin"
                ></i>
                {{ $gettext("Save") }}
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
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const gettext_provider = useGettext();
const { updateGitlabRepositoryArtifactClosure } = useNamespacedActions("gitlab", [
    "updateGitlabRepositoryArtifactClosure",
]);
const { artifact_closure_repository } = useNamespacedState("gitlab", [
    "artifact_closure_repository",
]);
const { setArtifactClosureModal } = useNamespacedMutations("gitlab", ["setArtifactClosureModal"]);
const { setSuccessMessage } = useMutations(["setSuccessMessage"]);

const modal = ref<Modal | null>(null);
const is_updating_gitlab_repository = ref(false);
const allow_artifact_closure = ref(false);
const message_error_rest = ref("");

const modal_element = ref();

const onShownModal = (): void => {
    allow_artifact_closure.value = artifact_closure_repository.value.allow_artifact_closure;
};

const reset = (): void => {
    is_updating_gitlab_repository.value = false;
    allow_artifact_closure.value = false;
    message_error_rest.value = "";
};

onMounted((): void => {
    modal.value = createModal(modal_element.value);
    modal.value.addEventListener("tlp-modal-shown", onShownModal);
    modal.value.addEventListener("tlp-modal-hidden", reset);
    setArtifactClosureModal(modal.value);
});

const have_any_rest_error = computed((): boolean => message_error_rest.value.length > 0);

const disabled_button = computed(
    () => is_updating_gitlab_repository.value || have_any_rest_error.value,
);

const close_label = gettext_provider.$gettext("Close");

const getSuccessMessage = (allow_closure_artifact: boolean): string => {
    if (allow_closure_artifact) {
        return gettext_provider.interpolate(
            gettext_provider.$gettext("Artifact closure is now allowed for '%{repository}'!"),
            { repository: artifact_closure_repository.value.label },
        );
    }
    return gettext_provider.interpolate(
        gettext_provider.$gettext("Artifact closure is now disabled for '%{repository}'!"),
        { repository: artifact_closure_repository.value.label },
    );
};

const updateArtifactClosureValue = async (event: Event): Promise<void> => {
    event.preventDefault();

    try {
        is_updating_gitlab_repository.value = true;

        const updated_integration = await updateGitlabRepositoryArtifactClosure({
            integration_id: Number(artifact_closure_repository.value.integration_id),
            allow_artifact_closure: allow_artifact_closure.value,
        });

        if (updated_integration) {
            modal.value?.hide();
        }

        const success_message = getSuccessMessage(updated_integration.allow_artifact_closure);
        setSuccessMessage(success_message);
    } catch (rest_error) {
        message_error_rest.value = await handleError(rest_error, gettext_provider);
        throw rest_error;
    } finally {
        is_updating_gitlab_repository.value = false;
    }
};

defineExpose({ message_error_rest });
</script>
