<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
                v-bind:id="`git-repository-card-link-${repository.id}`"
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
                        <i class="fa fa-cog" v-bind:title="administration_link_title"></i>
                    </a>
                    <div
                        v-if="is_admin && isGitlabRepository"
                        class="git-repository-card-admin-link"
                        data-test="git-repository-card-admin-unlink-gitlab"
                    >
                        <i
                            class="far fa-trash-alt unlink-repository-gitlab"
                            v-bind:title="unlink_repository_title"
                            v-bind:id="`unlink-gitlab-repository-${repository.id}`"
                            v-bind:data-test="`unlink-gitlab-repository-${repository.id}`"
                        ></i>
                    </div>
                </div>
                <section
                    class="tlp-pane-section git-repository-card-header"
                    v-if="hasRepositoryDescription || isGitlabRepository"
                >
                    <p
                        v-if="hasRepositoryDescription"
                        class="git-repository-card-description"
                        data-test="git-repository-card-description"
                    >
                        {{ repository.description }}
                    </p>
                    <div v-if="isGitlabRepository" class="git-repository-links-spacer"></div>
                    <i
                        v-if="isGitlabRepository"
                        class="fa fa-gitlab git-gitlab-icon"
                        data-test="git-repository-card-gitlab-icon"
                    ></i>
                </section>
            </a>
        </div>
    </section>
</template>
<script>
const DEFAULT_DESCRIPTION = "-- Default description --";

import { isGitlabRepository } from "../gitlab/gitlab-checker";
import { mapActions, mapGetters } from "vuex";
import TimeAgo from "javascript-time-ago";
import { getDashCasedLocale, getProjectId, getUserIsAdmin } from "../repository-list-presenter.js";
import PullRequestBadge from "./PullRequestBadge.vue";
import { getRepositoryListUrl } from "../breadcrumb-presenter.js";

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
        unlink_repository_title() {
            return this.$gettext("Unlink the repository");
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
                return this.repository.gitlab_data.full_url;
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
            const card_repository = document.getElementById(
                "git-repository-card-link-" + this.repository.id
            );
            const button_unlink = document.getElementById(
                "unlink-gitlab-repository-" + this.repository.id
            );
            if (card_repository && button_unlink) {
                card_repository.addEventListener("click", (event) => {
                    if (event.target === button_unlink) {
                        event.preventDefault();
                        this.showDeleteGitlabRepositoryModal(this.repository);
                        return;
                    }

                    event.stopPropagation();
                });
            }
        }
    },
    methods: {
        ...mapActions(["showDeleteGitlabRepositoryModal"]),
    },
};
</script>
