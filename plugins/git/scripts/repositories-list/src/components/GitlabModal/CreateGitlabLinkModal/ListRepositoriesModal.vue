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
                    <translate>Allow artifact closure</translate>
                </label>
                <p class="tlp-text-info">
                    <translate>
                        If selected, artifacts of this project can be closed with GitLab commit
                        messages from the selected repository.
                    </translate>
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

<script lang="ts">
import { PROJECT_KEY } from "../../../constants";
import { getProjectId } from "../../../repository-list-presenter";
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Getter, namespace } from "vuex-class";
import type { GitlabProject, GitlabDataWithPath } from "../../../type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { GitLabRepositoryCreation } from "../../../gitlab/gitlab-api-querier";

const gitlab = namespace("gitlab");

@Component
export default class ListRepositoriesModal extends Vue {
    @Prop({ required: true })
    readonly repositories!: GitlabProject[];
    @Prop({ required: true })
    readonly gitlab_api_token!: string;
    @Prop({ required: true })
    readonly server_url!: string;

    @gitlab.Action
    readonly postIntegrationGitlab!: (gitlab_data: GitLabRepositoryCreation) => Promise<void>;

    @Getter
    getGitlabRepositoriesIntegrated!: GitlabDataWithPath[];

    selected_repository: GitlabProject | null = null;
    is_loading = false;
    message_error_rest = "";
    allow_artifact_closure = false;

    get disabled_button(): boolean {
        return this.selected_repository === null || this.is_loading || this.have_any_rest_error;
    }

    get have_any_rest_error(): boolean {
        return this.message_error_rest.length > 0;
    }

    message_tooltip_repository_disabled(repository: GitlabProject): string {
        if (this.isRepositoryAlreadyIntegrated(repository)) {
            return this.$gettext("This repository is already integrated.");
        }

        return this.$gettext("A repository with same name and path was already integrated.");
    }

    async fetchRepositories(event: Event): Promise<void> {
        event.preventDefault();
        this.is_loading = true;
        try {
            if (!this.selected_repository) {
                return;
            }

            await this.postIntegrationGitlab({
                gitlab_repository_id: this.selected_repository.id,
                gitlab_bot_api_token: this.gitlab_api_token,
                gitlab_server_url: this.server_url,
                project_id: getProjectId(),
                allow_artifact_closure: this.allow_artifact_closure,
            });

            this.$store.commit("resetRepositories");
            this.$store.dispatch("changeRepositories", PROJECT_KEY);
            this.$emit("on-success-close-modal", { repository: this.selected_repository });
        } catch (rest_error) {
            await this.handleError(rest_error);
        } finally {
            this.is_loading = false;
        }
    }

    reset(): void {
        this.selected_repository = null;
        this.is_loading = false;
        this.message_error_rest = "";
    }

    async handleError(rest_error: unknown): Promise<void> {
        try {
            if (!(rest_error instanceof FetchWrapperError)) {
                throw rest_error;
            }
            const { error } = await rest_error.response.json();
            this.message_error_rest = error.code + ": " + error.message;
        } catch (error) {
            this.message_error_rest = this.$gettext("Oops, an error occurred!");
        } finally {
            this.is_loading = false;
        }
    }

    isRepositoryWithSameNamePath(repository: GitlabProject): boolean {
        return (
            this.getGitlabRepositoriesIntegrated.find((integrated_repository) => {
                return (
                    integrated_repository.normalized_path === repository.path_with_namespace &&
                    integrated_repository.gitlab_data.gitlab_repository_url !== repository.web_url
                );
            }) !== undefined
        );
    }

    isRepositoryAlreadyIntegrated(repository: GitlabProject): boolean {
        return (
            this.getGitlabRepositoriesIntegrated.find((integrated_repository) => {
                return (
                    integrated_repository.gitlab_data.gitlab_repository_id === repository.id &&
                    integrated_repository.gitlab_data.gitlab_repository_url === repository.web_url
                );
            }) !== undefined
        );
    }

    isRepositoryDisabled(repository: GitlabProject): boolean {
        return (
            this.isRepositoryWithSameNamePath(repository) ||
            this.isRepositoryAlreadyIntegrated(repository)
        );
    }

    selectRepository(repository: GitlabProject): void {
        if (!this.isRepositoryDisabled(repository)) {
            this.selected_repository = repository;
        }
    }
}
</script>
