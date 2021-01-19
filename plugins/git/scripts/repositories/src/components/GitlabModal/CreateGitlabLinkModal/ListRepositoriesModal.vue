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
            <table v-else class="tlp-table">
                <thead>
                    <tr>
                        <th></th>
                        <th colspan="2"><translate>Repository</translate></th>
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
                                    'gitlab-tooltip-button-radio tlp-tooltip tlp-tooltip-top': isRepositoryDisabled(
                                        repository
                                    ),
                                }"
                                v-bind:data-tlp-tooltip="
                                    message_tooltip_repository_disabled(repository)
                                "
                            >
                                <label class="tlp-radio">
                                    <input
                                        v-bind:disabled="isRepositoryDisabled(repository)"
                                        type="radio"
                                        v-bind:id="repository.id"
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
                                    'gitlab-tooltip-avatar tlp-tooltip tlp-tooltip-top': isRepositoryDisabled(
                                        repository
                                    ),
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
                                    'gitlab-tooltip-name tlp-tooltip tlp-tooltip-top': isRepositoryDisabled(
                                        repository
                                    ),
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
                <translate>Back</translate>
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
                <translate>Integrate selected repository</translate>
            </button>
        </div>
    </form>
</template>

<script>
import { PROJECT_KEY } from "../../../constants";
import { mapActions, mapGetters } from "vuex";
import { getProjectId } from "../../../repository-list-presenter";

export default {
    name: "ListRepositoriesModal",
    props: {
        repositories: {
            type: Array,
            default: () => [],
        },
        gitlab_project_token: {
            type: String,
            default: () => "",
        },
        server_url: {
            type: String,
            default: () => "",
        },
    },
    data() {
        return {
            selected_repository: null,
            is_loading: false,
            message_error_rest: "",
        };
    },
    computed: {
        ...mapGetters(["getGitlabRepositoriesIntegrated"]),
        disabled_button() {
            return this.selected_repository === null || this.is_loading || this.have_any_rest_error;
        },
        have_any_rest_error() {
            return this.message_error_rest.length > 0;
        },
    },
    methods: {
        ...mapActions(["postIntegrationGitlab"]),
        message_tooltip_repository_disabled(repository) {
            if (this.isRepositoryAlreadyIntegrated(repository)) {
                return this.$gettext("This repository is already integrated.");
            }

            return this.$gettext("A repository with same name and path was already integrated.");
        },
        async fetchRepositories(event) {
            event.preventDefault();
            this.is_loading = true;
            try {
                await this.postIntegrationGitlab({
                    gitlab_repository_id: this.selected_repository.id,
                    gitlab_bot_api_token: this.gitlab_project_token,
                    gitlab_server_url: this.server_url,
                    project_id: getProjectId(),
                });

                this.$store.commit("resetRepositories");
                this.$store.dispatch("changeRepositories", PROJECT_KEY);
                this.$emit("on-success-close-modal", { repository: this.selected_repository });
            } catch (rest_error) {
                this.has_rest_error = true;
                await this.handle_error(rest_error);
            } finally {
                this.is_loading = false;
            }
        },
        reset() {
            this.selected_repository = null;
            this.is_loading = false;
            this.message_error_rest = "";
        },

        async handle_error(rest_error) {
            try {
                const { error } = await rest_error.response.json();
                this.message_error_rest = error.code + ": " + error.message;
            } catch (error) {
                this.message_error_rest = this.$gettext("Oops, an error occurred!");
            } finally {
                this.is_loading = false;
            }
        },
        isRepositoryWithSameNamePath(repository) {
            return this.getGitlabRepositoriesIntegrated.find((repository_integrated) => {
                return (
                    repository_integrated.normalized_path === repository.path_with_namespace &&
                    repository_integrated.gitlab_data.gitlab_repository_url !== repository.web_url
                );
            });
        },
        isRepositoryAlreadyIntegrated(repository) {
            return this.getGitlabRepositoriesIntegrated.find((repository_integrated) => {
                return (
                    repository_integrated.gitlab_data.gitlab_repository_id === repository.id &&
                    repository_integrated.gitlab_data.gitlab_repository_url === repository.web_url
                );
            });
        },
        isRepositoryDisabled(repository) {
            return (
                this.isRepositoryWithSameNamePath(repository) ||
                this.isRepositoryAlreadyIntegrated(repository)
            );
        },
        selectRepository(repository) {
            if (!this.isRepositoryDisabled(repository)) {
                this.selected_repository = repository;
            }
        },
    },
};
</script>
