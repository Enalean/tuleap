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
            'git-repository-in-folder': isFolderDisplayMode && is_in_folder(),
        }"
        data-test="git-repository"
    >
        <div class="tlp-pane-container">
            <a
                v-bind:href="getRepositoryPath()"
                class="git-repository-card-link"
                data-test="git-repository-path"
            >
                <div class="tlp-pane-header git-repository-card-header">
                    <h2
                        class="tlp-pane-title git-repository-card-title"
                        data-test="repository_name"
                    >
                        <span
                            v-if="is_in_folder() && !isFolderDisplayMode"
                            class="git-repository-card-path"
                            data-test="git-repository-card-path"
                        >
                            {{ folder_path() }}
                        </span>
                        {{ repository_label() }}
                    </h2>
                    <div class="git-repository-links-spacer"></div>
                    <pull-request-badge
                        v-if="!isGitlabRepository(props.repository)"
                        v-bind:number_pull_request="number_pull_requests()"
                        v-bind:repository_id="Number(repository.id)"
                    />
                    <div class="git-repository-card-last-update">
                        <i class="far fa-clock git-repository-card-last-update-icon"></i>
                        {{ formatted_last_update_date() }}
                    </div>
                    <a
                        v-if="is_admin() && !isGitlabRepository(props.repository)"
                        v-bind:href="repository_admin_url()"
                        class="git-repository-card-admin-link"
                        data-test="git-repository-card-admin-link"
                    >
                        <i
                            class="fas fa-fw fa-cog"
                            v-bind:title="$gettext('Go to repository administration')"
                        ></i>
                    </a>
                    <git-lab-administration
                        v-if="isGitlabRepository(props.repository)"
                        v-bind:is_admin="is_admin()"
                        v-bind:repository="props.repository"
                    />
                </div>
                <section
                    class="tlp-pane-section git-repository-card-header"
                    v-if="
                        hasRepositoryDescription() ||
                        isGitlabRepository(props.repository) ||
                        isRepositoryHandledByGerrit(props.repository)
                    "
                >
                    <p
                        v-if="hasRepositoryDescription()"
                        class="git-repository-card-description"
                        v-bind:class="{
                            'gitlab-description': isGitlabRepository(props.repository),
                        }"
                        data-test="git-repository-card-description"
                    >
                        {{ repository.description }}
                    </p>
                    <div
                        v-if="mustDisplayAdditionalInformation()"
                        class="git-repository-links-spacer"
                    ></div>
                    <i
                        v-if="isRepositoryHandledByGerrit(props.repository)"
                        class="fas fa-tlp-gerrit git-gerrit-icon"
                        v-bind:title="$gettext('This repository is handled by Gerrit.')"
                        data-test="git-repository-card-gerrit-icon"
                    ></i>
                    <i
                        v-if="isGitlabRepository(props.repository)"
                        class="fab fa-gitlab git-gitlab-icon"
                        v-bind:class="{ 'git-gitlab-icon-align-to-date': !is_admin() }"
                        v-bind:title="$gettext('This repository comes from GitLab.')"
                        data-test="git-repository-card-gitlab-icon"
                    ></i>
                    <i
                        v-if="
                            isGitlabRepository(props.repository) &&
                            !isGitlabRepositoryWellConfigured(props.repository)
                        "
                        class="fas fa-exclamation-triangle git-gitlab-integration-not-well-configured"
                        v-bind:title="$gettext('Webhook must be regenerated.')"
                    ></i>
                </section>
            </a>
        </div>
    </section>
</template>
<script setup lang="ts">
import TimeAgo from "javascript-time-ago";
import GitLabAdministration from "./GitLabAdministration.vue";
import PullRequestBadge from "./PullRequestBadge.vue";
import { isGitlabRepository, isGitlabRepositoryWellConfigured } from "../gitlab/gitlab-checker";
import { isRepositoryHandledByGerrit } from "../gerrit/gerrit-checker";
import { getDashCasedLocale, getProjectId, getUserIsAdmin } from "../repository-list-presenter";
import { getRepositoryListUrl } from "../breadcrumb-presenter";
import type { FormattedGitLabRepository, Repository } from "../type";
import { useGetters } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";

const gettext_provider = useGettext();
const DEFAULT_DESCRIPTION = "-- Default description --";

const { isFolderDisplayMode } = useGetters(["isFolderDisplayMode"]);

const props = defineProps<{
    repository: Repository | FormattedGitLabRepository;
}>();

function mustDisplayAdditionalInformation(): boolean {
    return (
        isRepositoryHandledByGerrit(props.repository) ||
        isGitlabRepository(props.repository) ||
        isGitlabRepositoryWellConfigured(props.repository)
    );
}

function hasRepositoryDescription(): boolean {
    return props.repository.description !== DEFAULT_DESCRIPTION;
}

function repository_admin_url(): string {
    return `/plugins/git/?action=repo_management&group_id=${getProjectId()}&repo_id=${
        props.repository.id
    }`;
}

function is_admin(): boolean {
    return getUserIsAdmin();
}

function formatted_last_update_date(): string {
    const date = new Date(props.repository.last_update_date);
    const time_ago = new TimeAgo(getDashCasedLocale());

    return gettext_provider.interpolate(gettext_provider.$gettext("Updated %{time_ago}"), {
        time_ago: time_ago.format(date),
    });
}

function isRepository(
    repository: Repository | FormattedGitLabRepository,
): repository is Repository {
    return Object.prototype.hasOwnProperty.call(repository, "permissions");
}

function number_pull_requests(): number {
    if (isRepository(props.repository)) {
        return Number.parseInt(props.repository.additional_information.opened_pull_requests, 10);
    }

    return 0;
}

function repository_label(): string {
    return props.repository.label;
}

function is_in_folder(): number {
    return props.repository.path_without_project.length;
}

function getRepositoryPath(): string {
    if (isGitlabRepository(props.repository) && props.repository.gitlab_data) {
        return props.repository.gitlab_data.gitlab_repository_url;
    }
    return getRepositoryListUrl() + props.repository.normalized_path;
}

function folder_path(): string {
    return props.repository.path_without_project + "/";
}
</script>
