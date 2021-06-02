<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
  -->

<template>
    <section
        class="tlp-pane git-repository-card"
        v-bind:class="{
            'git-repository-card-two-columns': !isFolderDisplayMode,
            'git-repository-in-folder': isFolderDisplayMode && is_in_folder,
        }"
    >
        <div class="tlp-pane-container">
            <a
                v-bind:href="getRepositoryPath"
                class="git-repository-card-link"
                data-test="git-repository-path"
            >
                <div class="tlp-pane-header git-repository-card-header">
                    <h2
                        class="tlp-pane-title git-repository-card-title"
                        data-test="repository_name"
                    >
                        <span
                            v-if="is_in_folder && !isFolderDisplayMode"
                            class="git-repository-card-path"
                            data-test="git-repository-card-path"
                        >
                            {{ folder_path }}
                        </span>
                        {{ repository_label }}
                    </h2>
                    <div class="git-repository-links-spacer"></div>
                    <pull-request-badge
                        v-if="!isGitlabRepository"
                        v-bind:number-pull-request="number_pull_requests"
                        v-bind:repository-id="repository.id"
                    />
                    <div class="git-repository-card-last-update">
                        <i class="far fa-clock git-repository-card-last-update-icon"></i>
                        <translate>Updated %{ formatted_last_update_date }</translate>
                    </div>
                    <a
                        v-if="is_admin && !isGitlabRepository"
                        v-bind:href="repository_admin_url"
                        class="git-repository-card-admin-link"
                        data-test="git-repository-card-admin-link"
                    >
                        <i class="fas fa-fw fa-cog" v-bind:title="administration_link_title"></i>
                    </a>
                    <div
                        v-if="is_admin && isGitlabRepository"
                        class="git-repository-card-admin-link"
                        data-test="git-repository-card-admin-unlink-gitlab"
                        ref="gitlab_administration"
                    >
                        <i
                            class="fas fa-fw fa-cog git-repository-card-admin-link"
                            v-bind:title="administration_gitlab_title"
                            ref="dropdown_gitlab_administration"
                            v-bind:data-test="`dropdown-gitlab-administration-${repository.id}`"
                        ></i>
                        <div
                            class="
                                tlp-dropdown-menu tlp-dropdown-menu-on-icon tlp-dropdown-menu-right
                                gitlab-administration-dropdown
                            "
                            ref="dropdown_gitlab_administration_menu_options"
                            data-test="dropdown-gitlab-administration-menu-options"
                            role="menu"
                        >
                            <div
                                class="tlp-dropdown-menu-item"
                                role="menuitem"
                                ref="edit_access_token_gitlab_repository"
                                data-test="edit-access-token-gitlab-repository"
                            >
                                <i
                                    class="fas fa-fw fa-key tlp-dropdown-menu-item-icon"
                                    aria-hidden="true"
                                ></i>
                                {{ edit_access_token_repository_title }}
                            </div>
                            <div
                                class="tlp-dropdown-menu-item"
                                role="menuitem"
                                ref="regenerate_gitlab_webhook"
                                data-test="regenerate-webhook-gitlab-repository"
                            >
                                <i
                                    class="fas fa-tlp-webhooks fa-fw tlp-dropdown-menu-item-icon"
                                    aria-hidden="true"
                                ></i>
                                {{ regenerate_gitlab_webhook_title }}
                            </div>
                            <div
                                class="tlp-dropdown-menu-item"
                                role="menuitem"
                                ref="artifact_closure"
                                data-test="artifact-closure-gitlab-repository"
                            >
                                <i
                                    class="far fa-fw fa-times-circle tlp-dropdown-menu-item-icon"
                                    aria-hidden="true"
                                ></i>
                                <translate>Allow artifact closure</translate>
                            </div>
                            <div
                                class="tlp-dropdown-menu-item unlink-repository-gitlab"
                                role="menuitem"
                                ref="unlink_gitlab_repository"
                                v-bind:data-test="`unlink-gitlab-repository-${repository.id}`"
                            >
                                <i
                                    class="far fa-fw fa-trash-alt tlp-dropdown-menu-item-icon"
                                    v-bind:title="unlink_repository_title"
                                ></i>
                                {{ unlink_repository_title }}
                            </div>
                        </div>
                    </div>
                </div>
                <section
                    class="tlp-pane-section git-repository-card-header"
                    v-if="
                        hasRepositoryDescription ||
                        isGitlabRepository ||
                        isRepositoryHandledByGerrit
                    "
                >
                    <p
                        v-if="hasRepositoryDescription"
                        class="git-repository-card-description"
                        v-bind:class="{ 'gitlab-description': isGitlabRepository }"
                        data-test="git-repository-card-description"
                    >
                        {{ repository.description }}
                    </p>
                    <div
                        v-if="mustDisplayAdditionalInformation"
                        class="git-repository-links-spacer"
                    ></div>
                    <i
                        v-if="isRepositoryHandledByGerrit"
                        class="fas fa-tlp-gerrit git-gerrit-icon"
                        v-bind:title="$gettext('This repository is handled by Gerrit.')"
                        data-test="git-repository-card-gerrit-icon"
                    ></i>
                    <i
                        v-if="isGitlabRepository"
                        class="fab fa-gitlab git-gitlab-icon"
                        v-bind:class="{ 'git-gitlab-icon-align-to-date': !is_admin }"
                        v-bind:title="$gettext('This repository comes from GitLab.')"
                        data-test="git-repository-card-gitlab-icon"
                    ></i>
                    <i
                        v-if="isGitlabRepository && !isGitlabRepositoryWellConfigured"
                        class="
                            fas
                            fa-exclamation-triangle
                            git-gitlab-integration-not-well-configured
                        "
                        v-bind:title="$gettext('Webhook must be regenerated.')"
                    ></i>
                </section>
            </a>
        </div>
    </section>
</template>
<script>
const DEFAULT_DESCRIPTION = "-- Default description --";

import { isGitlabRepository, isGitlabRepositoryWellConfigured } from "../gitlab/gitlab-checker";
import { isRepositoryHandledByGerrit } from "../gerrit/gerrit-checker";
import { mapActions, mapGetters } from "vuex";
import TimeAgo from "javascript-time-ago";
import { getDashCasedLocale, getProjectId, getUserIsAdmin } from "../repository-list-presenter";
import PullRequestBadge from "./PullRequestBadge.vue";
import { getRepositoryListUrl } from "../breadcrumb-presenter";
import { createDropdown } from "tlp";

export default {
    name: "GitRepository",
    components: {
        PullRequestBadge,
    },
    props: {
        repository: {
            type: Object,
            default: () => {
                return {};
            },
        },
    },
    computed: {
        isGitlabRepository() {
            return isGitlabRepository(this.repository);
        },
        isGitlabRepositoryWellConfigured() {
            return isGitlabRepositoryWellConfigured(this.repository);
        },
        isRepositoryHandledByGerrit() {
            return isRepositoryHandledByGerrit(this.repository);
        },
        mustDisplayAdditionalInformation() {
            return (
                this.isRepositoryHandledByGerrit ||
                this.isGitlabRepository ||
                this.isGitlabRepositoryWellConfigured
            );
        },
        hasRepositoryDescription() {
            return this.repository.description !== DEFAULT_DESCRIPTION;
        },
        repository_admin_url() {
            return `/plugins/git/?action=repo_management&group_id=${getProjectId()}&repo_id=${
                this.repository.id
            }`;
        },
        is_admin() {
            return getUserIsAdmin();
        },
        administration_link_title() {
            return this.$gettext("Go to repository administration");
        },
        administration_gitlab_title() {
            return this.$gettext("Manage the repository");
        },
        unlink_repository_title() {
            return this.$gettext("Unlink the repository");
        },
        regenerate_gitlab_webhook_title() {
            return this.$gettext("Regenerate the GitLab webhook");
        },
        edit_access_token_repository_title() {
            return this.$gettext("Edit access token");
        },
        formatted_last_update_date() {
            const date = new Date(this.repository.last_update_date);
            const time_ago = new TimeAgo(getDashCasedLocale());
            return time_ago.format(date);
        },
        number_pull_requests() {
            return Number.parseInt(this.repository.additional_information.opened_pull_requests, 10);
        },
        repository_label() {
            return this.repository.label;
        },
        is_in_folder() {
            return this.repository.path_without_project.length;
        },
        getRepositoryPath() {
            if (this.isGitlabRepository) {
                return this.repository.gitlab_data.gitlab_repository_url;
            }
            return getRepositoryListUrl() + this.repository.normalized_path;
        },
        folder_path() {
            return this.repository.path_without_project + "/";
        },
        ...mapGetters(["isFolderDisplayMode"]),
    },
    mounted() {
        if (this.isGitlabRepository) {
            const button_unlink = this.$refs.unlink_gitlab_repository;

            if (button_unlink) {
                button_unlink.addEventListener("click", (event) => {
                    event.preventDefault();
                    this.showDeleteGitlabRepositoryModal(this.repository);
                });
            }

            const button_edit_access_token = this.$refs.edit_access_token_gitlab_repository;

            if (button_edit_access_token) {
                button_edit_access_token.addEventListener("click", (event) => {
                    event.preventDefault();
                    this.showEditAccessTokenGitlabRepositoryModal(this.repository);
                });
            }

            const button_regenerate_gitlab_webhook = this.$refs.regenerate_gitlab_webhook;

            if (button_regenerate_gitlab_webhook) {
                button_regenerate_gitlab_webhook.addEventListener("click", (event) => {
                    event.preventDefault();
                    this.showRegenerateGitlabWebhookModal(this.repository);
                });
            }

            const button_artifact_closure = this.$refs.artifact_closure;

            if (button_artifact_closure) {
                button_artifact_closure.addEventListener("click", (event) => {
                    event.preventDefault();
                    this.showArtifactClosureModal(this.repository);
                });
            }

            const button_gitlab_administration = this.$refs.gitlab_administration;
            const dropdown_gitlab_administration = this.$refs.dropdown_gitlab_administration;

            if (button_gitlab_administration && dropdown_gitlab_administration) {
                button_gitlab_administration.addEventListener("click", (event) => {
                    event.preventDefault();
                });
                createDropdown(dropdown_gitlab_administration, {
                    keyboard: false,
                    dropdown_menu: this.$refs.dropdown_gitlab_administration_menu_options,
                });
            }
        }
    },
    methods: {
        ...mapActions("gitlab", [
            "showDeleteGitlabRepositoryModal",
            "showEditAccessTokenGitlabRepositoryModal",
            "showRegenerateGitlabWebhookModal",
            "showArtifactClosureModal",
        ]),
    },
};
</script>
