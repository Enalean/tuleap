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
    <form v-on:submit="fetchRepositories" data-test="fetch-gitlab-repository-modal-form">
        <div class="tlp-modal-body git-repository-create-modal-body">
            <div
                class="tlp-alert-danger"
                data-test="gitlab-fail-load-repositories"
                v-if="error_message.length > 0"
            >
                {{ error_message }}
            </div>
            <div
                class="tlp-alert-warning"
                data-test="gitlab-empty-repositories"
                v-if="empty_message.length > 0"
            >
                {{ empty_message }}
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="gitlab_server">
                    {{ $gettext("GitLab server URL") }}
                    <i class="fas fa-asterisk" aria-hidden="true"></i>
                </label>
                <input
                    type="url"
                    class="tlp-input"
                    id="gitlab_server"
                    required
                    v-model="gitlab_server"
                    placeholder="https://example.com"
                    pattern="https://.+"
                    maxlength="255"
                    data-test="add_gitlab_server"
                />
            </div>

            <div class="tlp-form-element">
                <label class="tlp-label" for="gitlab_project_token">
                    {{ $gettext("GitLab access token (personal or project)") }}
                    <i class="fas fa-asterisk" aria-hidden="true"></i>
                </label>
                <input
                    type="password"
                    class="tlp-input"
                    id="gitlab_project_token"
                    required
                    v-model="gitlab_token"
                    maxlength="255"
                    data-test="add_gitlab_project_token"
                    autocomplete="off"
                />
                <p class="tlp-text-info gitlab-test-info-form-token-modal">
                    {{
                        $gettext(
                            "The access token will be used to fetch repositories, configure project hooks and automatically write comments on GitLab commit and merge requests.",
                        )
                    }}"
                </p>
                <p class="tlp-text-info">
                    {{ $gettext("GitLab access token scope must contain at least: api.") }}
                </p>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-on:click="$emit('on-close-modal')"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="disabled_button"
                data-test="button-add-gitlab-repository"
            >
                <i
                    class="fas tlp-button-icon"
                    v-bind:class="{
                        'fa-spin fa-circle-notch': is_loading,
                        'fa-long-arrow-alt-right': !is_loading,
                    }"
                    data-test="icon-spin"
                ></i>
                {{ $gettext("Fetch GitLab repositories") }}
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { credentialsAreEmpty, serverUrlIsValid } from "../../../gitlab/gitlab-credentials-helper";
import { computed, ref } from "vue";
import type { GitLabCredentialsWithProjects, GitlabProject } from "../../../type";
import { useNamespacedActions } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";

const gettext_provider = useGettext();

const { getGitlabProjectList } = useNamespacedActions("gitlab", ["getGitlabProjectList"]);

const props = defineProps<{
    gitlab_api_token: string;
    server_url: string;
}>();

const emit = defineEmits<{
    (
        e: "on-get-gitlab-repositories",
        { projects, token, server_url }: GitLabCredentialsWithProjects,
    ): void;
    (e: "on-close-modal"): void;
}>();

const gitlab_server = ref(props.server_url);
const gitlab_token = ref(props.gitlab_api_token);
const is_loading = ref(false);
const error_message = ref("");
const empty_message = ref("");
const gitlab_projects = ref<null | GitlabProject[]>(null);

const disabled_button = computed((): boolean => {
    return gitlab_server.value === "" || gitlab_token.value === "" || is_loading.value;
});

function reset(): void {
    gitlab_server.value = "";
    gitlab_token.value = "";
    is_loading.value = false;
    gitlab_projects.value = null;
    resetMessages();
}

function resetMessages(): void {
    error_message.value = "";
    empty_message.value = "";
}

const handleError = (): void => {
    resetMessages();
    error_message.value = gettext_provider.$gettext(
        "Cannot connect to GitLab server, please check your credentials.",
    );
};

const fetchRepositories = async (event: Event): Promise<void> => {
    event.preventDefault();
    resetMessages();

    const credentials = {
        server_url: gitlab_server.value,
        token: gitlab_token.value,
    };

    if (credentialsAreEmpty(credentials)) {
        error_message.value = gettext_provider.$gettext(
            "You must provide a valid GitLab server and user API token",
        );
        return;
    }

    if (!serverUrlIsValid(credentials.server_url)) {
        error_message.value = gettext_provider.$gettext("Server url is invalid");
        return;
    }

    try {
        is_loading.value = true;
        gitlab_projects.value = await getGitlabProjectList(credentials);

        if (gitlab_projects.value === null) {
            empty_message.value = gettext_provider.$gettext(
                "No repository is available with your GitLab account",
            );
            return;
        }

        emit("on-get-gitlab-repositories", {
            projects: gitlab_projects.value,
            token: gitlab_token.value,
            server_url: gitlab_server.value,
        });
    } catch (_e) {
        handleError();
    } finally {
        is_loading.value = false;
    }
};

defineExpose({
    reset,
    is_loading,
    gitlab_server,
    gitlab_token,
    gitlab_projects,
    empty_message,
    error_message,
    disabled_button,
});
</script>
