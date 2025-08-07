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
    <form v-on:submit="addGitlabToken" data-test="edit-token-gitlab-repository-modal-form">
        <div class="tlp-modal-body">
            <p>
                {{ tokenAPIInformationMessage() }}
            </p>
            <div
                class="tlp-alert-danger"
                data-test="gitlab-fail-check-new-token"
                v-if="error_message.length > 0"
            >
                {{ error_message }}
            </div>
            <div class="tlp-property">
                <label class="tlp-label">
                    {{ $gettext("GitLab server URL") }}
                </label>
                <p>{{ instance_url }}</p>
            </div>
            <div class="tlp-property">
                <label class="tlp-label">
                    {{ $gettext("GitLab repository") }}
                </label>
                <p>{{ repository.normalized_path }}</p>
            </div>

            <div
                class="tlp-form-element"
                v-bind:class="{ 'tlp-form-element-error': error_message.length > 0 }"
            >
                <label class="tlp-label" for="gitlab_new_token">
                    {{ $gettext("GitLab access token (personal or project)") }}
                    <i class="fas fa-asterisk" aria-hidden="true"></i>
                </label>
                <input
                    type="password"
                    class="tlp-input"
                    id="gitlab_new_token"
                    required
                    v-model="gitlab_new_token"
                    data-test="add_gitlab_new_token"
                    autocomplete="off"
                />
                <p class="tlp-text-info">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    {{ $gettext("GitLab access token scope must contain at least: api.") }}
                </p>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-on:click="cancelButton"
                data-test="button-cancel-new-token-gitlab-repository"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="disabled_button"
                data-test="button-check-new-token-gitlab-repository"
            >
                <i
                    v-if="is_checking_validity_of_new_token"
                    class="fas tlp-button-icon fa-spin fa-circle-notch"
                    data-test="icon-spin"
                    aria-hidden="true"
                ></i>
                {{ $gettext("Check new token validity") }}
                <i class="fas tlp-button-icon fa-long-arrow-alt-right" aria-hidden="true"></i>
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { credentialsAreEmpty } from "../../../gitlab/gitlab-credentials-helper";
import { computed, ref } from "vue";
import type { Repository } from "../../../type";
import { useNamespacedActions } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";

const gettext_provider = useGettext();

const { getGitlabRepositoryFromId } = useNamespacedActions("gitlab", ["getGitlabRepositoryFromId"]);

const props = defineProps<{
    repository: Repository;
    gitlab_token: string;
}>();

const emit = defineEmits<{
    (e: "on-close-modal"): void;
    (e: "on-get-new-token-gitlab", { token }: { token: string }): void;
}>();

const gitlab_new_token = ref(props.gitlab_token);
const error_message = ref("");
const is_checking_validity_of_new_token = ref(false);

const instance_url = computed((): string => {
    if (!props.repository.gitlab_data || !props.repository.normalized_path) {
        return "";
    }
    return props.repository.gitlab_data.gitlab_repository_url.replace(
        props.repository.normalized_path,
        "",
    );
});

const disabled_button = computed(
    (): boolean => gitlab_new_token.value === "" || is_checking_validity_of_new_token.value,
);

const tokenAPIInformationMessage = (): string => {
    return gettext_provider.$gettext(
        "The access token will be used to configure project hooks and automatically write comments on GitLab commits and merge requests. It's also needed to be able to extract Tuleap references from GitLab tag message.",
    );
};

const resetErrorMessage = (): void => {
    error_message.value = "";
};

const reset = (): void => {
    gitlab_new_token.value = "";
    is_checking_validity_of_new_token.value = false;
    resetErrorMessage();
};

const cancelButton = (): void => {
    reset();
    emit("on-close-modal");
};

const addGitlabToken = async (event: Event): Promise<void> => {
    event.preventDefault();
    resetErrorMessage();

    const credentials = {
        server_url: instance_url.value,
        token: gitlab_new_token.value,
    };

    if (credentialsAreEmpty(credentials)) {
        error_message.value = gettext_provider.$gettext(
            "You must provide a valid GitLab API token",
        );
        return;
    }

    try {
        is_checking_validity_of_new_token.value = true;

        if (!props.repository.gitlab_data) {
            return;
        }

        await getGitlabRepositoryFromId({
            credentials,
            id: props.repository.gitlab_data.gitlab_repository_id,
        });

        emit("on-get-new-token-gitlab", {
            token: gitlab_new_token.value,
        });
    } catch (e) {
        error_message.value = gettext_provider.$gettext(
            "Submitted token is invalid to access to this repository on this GitLab server.",
        );
    } finally {
        is_checking_validity_of_new_token.value = false;
    }
};

defineExpose({ gitlab_new_token, error_message, disabled_button });
</script>
