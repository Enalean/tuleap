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
    <form
        role="dialog"
        aria-labelledby="select-gitlab-repository-modal-title"
        id="select-gitlab-repositories-modal"
        ref="select_modal"
        v-on:submit="fetchRepositories"
        data-test="select-gitlab-repository-modal-form"
    >
        <div class="tlp-modal-body git-repository-create-modal-body">
            <div
                class="tlp-alert-danger"
                data-test="gitlab-fail-post-repositories"
                v-if="have_any_rest_error"
            >
                {{ message_error_rest }}
            </div>
            <table v-else class="tlp-table gitlab-repositories-list-table">
                <thead>
                    <tr>
                        <th></th>
                        <th colspan="2">
                            {{ $gettext("Repository") }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="repository of repositories"
                        v-bind:key="repository.id"
                        v-bind:data-test="`gitlab-repositories-displayed-${repository.id}`"
                        v-bind:class="{
                            'gitlab-select-repository-disabled': isRepositoryDisabled(repository),
                        }"
                        class="gitlab-select-repository"
                        v-on:click="selectRepository(repository)"
                    >
                        <td class="gitlab-select-radio-button-container">
                            <span
                                v-bind:class="{
                                    'gitlab-tooltip-button-radio tlp-tooltip tlp-tooltip-top':
                                        isRepositoryDisabled(repository),
                                }"
                                v-bind:data-tlp-tooltip="
                                    message_tooltip_repository_disabled(repository)
                                "
                            >
                                <label class="tlp-radio">
                                    <input
                                        v-bind:disabled="isRepositoryDisabled(repository)"
                                        type="radio"
                                        v-bind:id="String(repository.id)"
                                        v-bind:value="repository"
                                        v-model="selected_repository"
                                        class="gitlab-select-radio-button"
                                        v-bind:data-test="`gitlab-repository-disabled-${repository.id}`"
                                    />
                                </label>
                            </span>
                        </td>
                        <td
                            class="gitlab-select-avatar"
                            v-bind:data-test="`gitlab-avatar-${repository.id}`"
                        >
                            <span
                                v-bind:class="{
                                    'gitlab-tooltip-avatar tlp-tooltip tlp-tooltip-top':
                                        isRepositoryDisabled(repository),
                                }"
                                v-bind:data-tlp-tooltip="
                                    message_tooltip_repository_disabled(repository)
                                "
                            >
                                <img
                                    v-if="repository.avatar_url !== null"
                                    v-bind:src="repository.avatar_url"
                                    v-bind:alt="repository.path_with_namespace"
                                    class="gitlab-avatar"
                                />
                                <div v-else class="default-gitlab-avatar gitlab-avatar">
                                    {{ repository.name[0] }}
                                </div>
                            </span>
                        </td>
                        <td
                            class="gitlab-repository-namespace"
                            v-bind:data-test="`gitlab-label-path-${repository.id}`"
                        >
                            <span
                                v-bind:class="{
                                    'gitlab-tooltip-name tlp-tooltip tlp-tooltip-top':
                                        isRepositoryDisabled(repository),
                                }"
                                v-bind:data-tlp-tooltip="
                                    message_tooltip_repository_disabled(repository)
                                "
                                v-bind:data-test="`gitlab-repositories-tooltip-${repository.id}`"
                            >
                                <span class="gitlab-repository-name-namespace">
                                    {{ repository.name_with_namespace }}
                                    <span class="gitlab-repository-path-namespace">
                                        ({{ repository.path_with_namespace }})
                                    </span>
                                </span>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="tlp-form-element">
                <label class="tlp-label tlp-checkbox">
                    <input type="checkbox" v-model="allow_artifact_closure" value="true" />
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
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-test="gitlab-button-back"
                v-on:click="$emit('to-back-button')"
            >
                <i
                    class="fas fa-long-arrow-alt-left tlp-button-icon"
                    data-test="icon-back-button"
                ></i>
                {{ $gettext("Back") }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="disabled_button"
                data-test="button-integrate-gitlab-repository"
            >
                <i
                    class="fas tlp-button-icon"
                    data-test="icon-spin"
                    v-bind:class="{
                        'fa-spin fa-circle-notch': is_loading,
                        'fa-long-arrow-alt-right': !is_loading,
                    }"
                ></i>
                {{ $gettext("Integrate selected repository") }}
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { PROJECT_KEY } from "../../../constants";
import { getProjectId } from "../../../repository-list-presenter";
import { computed, ref } from "vue";
import type { FormattedGitLabRepository, GitlabProject, Repository } from "../../../type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { useActions, useMutations, useNamespacedActions, useStore } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";

const { postIntegrationGitlab } = useNamespacedActions("gitlab", ["postIntegrationGitlab"]);
const { resetRepositories } = useMutations(["resetRepositories"]);
const { changeRepositories } = useActions(["changeRepositories"]);

const store = useStore();
const getGitlabRepositoriesIntegrated = computed(() => {
    return store.getters.getGitlabRepositoriesIntegrated;
});

const gettext_provider = useGettext();

const props = defineProps<{
    repositories: GitlabProject[];
    gitlab_api_token: string;
    server_url: string;
}>();

const emit = defineEmits<{
    (e: "on-success-close-modal", { repository }: { repository: GitlabProject }): void;
    (e: "to-back-button"): void;
}>();

const selected_repository = ref<GitlabProject | null>(null);
const is_loading = ref(false);
const message_error_rest = ref("");
const allow_artifact_closure = ref(false);

const have_any_rest_error = computed((): boolean => {
    return message_error_rest.value.length > 0;
});

const disabled_button = computed((): boolean => {
    return selected_repository.value === null || is_loading.value || have_any_rest_error.value;
});

const isRepositoryAlreadyIntegrated = (repository: GitlabProject): boolean => {
    return (
        getGitlabRepositoriesIntegrated.value.find(
            (integrated_repository: FormattedGitLabRepository | Repository) => {
                return (
                    integrated_repository.gitlab_data?.gitlab_repository_id === repository.id &&
                    integrated_repository.gitlab_data?.gitlab_repository_url === repository.web_url
                );
            },
        ) !== undefined
    );
};

const message_tooltip_repository_disabled = (repository: GitlabProject): string => {
    if (isRepositoryAlreadyIntegrated(repository)) {
        return gettext_provider.$gettext("This repository is already integrated.");
    }

    return gettext_provider.$gettext(
        "A repository with same name and path was already integrated.",
    );
};

const handleError = async (rest_error: unknown): Promise<void> => {
    try {
        if (!(rest_error instanceof FetchWrapperError)) {
            throw rest_error;
        }
        const { error } = await rest_error.response.json();
        message_error_rest.value = error.code + ": " + error.message;
    } catch (_error) {
        message_error_rest.value = gettext_provider.$gettext("Oops, an error occurred!");
    } finally {
        is_loading.value = false;
    }
};

const fetchRepositories = async (event: Event): Promise<void> => {
    event.preventDefault();
    is_loading.value = true;
    try {
        if (!selected_repository.value) {
            return;
        }

        await postIntegrationGitlab({
            gitlab_repository_id: selected_repository.value.id,
            gitlab_bot_api_token: props.gitlab_api_token,
            gitlab_server_url: props.server_url,
            project_id: getProjectId(),
            allow_artifact_closure: allow_artifact_closure.value,
        });

        resetRepositories();
        changeRepositories(PROJECT_KEY);
        emit("on-success-close-modal", { repository: selected_repository.value });
    } catch (rest_error) {
        await handleError(rest_error);
    } finally {
        is_loading.value = false;
    }
};

const reset = (): void => {
    selected_repository.value = null;
    is_loading.value = false;
    message_error_rest.value = "";
};

const isRepositoryWithSameNamePath = (repository: GitlabProject): boolean => {
    return (
        getGitlabRepositoriesIntegrated.value.find(
            (integrated_repository: FormattedGitLabRepository | Repository) => {
                return (
                    integrated_repository.normalized_path === repository.path_with_namespace &&
                    integrated_repository.gitlab_data?.gitlab_repository_url !== repository.web_url
                );
            },
        ) !== undefined
    );
};

const isRepositoryDisabled = (repository: GitlabProject): boolean => {
    return isRepositoryWithSameNamePath(repository) || isRepositoryAlreadyIntegrated(repository);
};

const selectRepository = (repository: GitlabProject): void => {
    if (!isRepositoryDisabled(repository)) {
        selected_repository.value = repository;
    }
};

defineExpose({
    reset,
    selected_repository,
    is_loading,
    message_error_rest,
    disabled_button,
});
</script>
