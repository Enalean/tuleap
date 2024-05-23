<!--
  - Copyright (c) Enalean 2018 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div>
        <git-breadcrumbs />
        <div class="git-repository-list-header">
            <h1>{{ $gettext("Git repositories") }}</h1>
            <jenkins-servers v-if="has_jenkins_server()" v-bind:servers="jenkins_servers()" />
        </div>
        <action-bar />
        <div class="tlp-framed">
            <error-message />
            <success-message />
            <git-repository-create />
            <gitlab-repository-modal />
            <unlink-repository-gitlab-modal />
            <edit-access-token-gitlab-modal />
            <folder-repository-list v-if="isFolderDisplayMode" />
            <repository-list v-else />
            <repository-list-spinner />
            <filter-empty-state />
            <no-repository-empty-state />
            <regenerate-gitlab-webhook />
            <artifact-closure-modal />
            <create-branch-prefix-modal />
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { Getter } from "vuex-class";
import GitRepositoryCreate from "./GitRepositoryCreate.vue";
import FilterEmptyState from "./FilterEmptyState.vue";
import GitBreadcrumbs from "./GitBreadcrumbs.vue";
import NoRepositoryEmptyState from "./NoRepositoryEmptyState.vue";
import ActionBar from "./ActionBar.vue";
import RepositoryList from "./RepositoryList.vue";
import ErrorMessage from "./ErrorMessage.vue";
import RepositoryListSpinner from "./RepositoryListSpinner.vue";
import FolderRepositoryList from "./folders/FolderRepositoryList.vue";
import { PROJECT_KEY } from "../constants";
import { getExternalPlugins } from "../repository-list-presenter";
import JenkinsServers from "./ActionBar/JenkinsServers.vue";
import GitlabRepositoryModal from "./GitlabModal/CreateGitlabLinkModal/GitlabRepositoryModal.vue";
import EditAccessTokenGitlabModal from "./GitlabModal/EditAccessTokenGitlabModal/EditAccessTokenGitlabModal.vue";
import UnlinkRepositoryGitlabModal from "./GitlabModal/UnlinkGitlabRepositoryModal/UnlinkRepositoryGitlabModal.vue";
import SuccessMessage from "./SuccessMessage.vue";
import RegenerateGitlabWebhook from "./GitlabModal/RegenerateGitlabWebhookModal/RegenerateGitlabWebhook.vue";
import ArtifactClosureModal from "./GitlabModal/ArtifactClosureModal/ArtifactClosureModal.vue";
import CreateBranchPrefixModal from "./GitlabModal/CreateBranchPrefix/CreateBranchPrefixModal.vue";

@Component({
    components: {
        ArtifactClosureModal,
        CreateBranchPrefixModal,
        RegenerateGitlabWebhook,
        SuccessMessage,
        GitlabRepositoryModal,
        UnlinkRepositoryGitlabModal,
        EditAccessTokenGitlabModal,
        ErrorMessage,
        RepositoryList,
        ActionBar,
        NoRepositoryEmptyState,
        FilterEmptyState,
        GitRepositoryCreate,
        GitBreadcrumbs,
        RepositoryListSpinner,
        FolderRepositoryList,
        JenkinsServers,
    },
})
export default class App extends Vue {
    @Getter
    readonly isFolderDisplayMode!: boolean;

    async mounted(): Promise<void> {
        await this.$store.dispatch("changeRepositories", PROJECT_KEY);
    }

    has_jenkins_server(): boolean {
        return (
            getExternalPlugins().find((plugin) => {
                return plugin.plugin_name === "hudson_git" && plugin.data.length > 0;
            }) !== undefined
        );
    }
    jenkins_servers(): Array<unknown> {
        const external_plugin = getExternalPlugins().find((plugin) => {
            return plugin.plugin_name === "hudson_git";
        });
        if (!external_plugin) {
            return [];
        }

        return external_plugin.data;
    }
}
</script>
