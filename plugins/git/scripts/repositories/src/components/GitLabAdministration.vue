<!--
  - Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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
    <div
        v-if="is_admin"
        class="git-repository-card-admin-link"
        data-test="git-repository-card-admin-unlink-gitlab"
        ref="gitlab_administration"
    >
        <i
            class="fas fa-fw fa-cog git-repository-card-admin-link"
            v-bind:title="$gettext('Manage the repository')"
            ref="dropdown_gitlab_administration"
            v-bind:data-test="`dropdown-gitlab-administration-${repository.id}`"
        ></i>
        <div
            class="tlp-dropdown-menu tlp-dropdown-menu-on-icon tlp-dropdown-menu-right gitlab-administration-dropdown"
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
                <i class="fas fa-fw fa-key tlp-dropdown-menu-item-icon" aria-hidden="true"></i>
                <translate>Edit access token</translate>
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
                <translate>Regenerate the GitLab webhook</translate>
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
            <div class="tlp-dropdown-menu-item" role="menuitem" ref="create_branch_prefix">
                <i
                    class="fas fa-code-branch fa-fw tlp-dropdown-menu-item-icon"
                    aria-hidden="true"
                ></i>
                <translate>Create branch prefix</translate>
            </div>
            <div
                class="tlp-dropdown-menu-item unlink-repository-gitlab"
                role="menuitem"
                ref="unlink_gitlab_repository"
                v-bind:data-test="`unlink-gitlab-repository-${repository.id}`"
            >
                <i
                    class="far fa-fw fa-trash-alt tlp-dropdown-menu-item-icon"
                    v-bind:title="$gettext('Unlink the repository')"
                ></i>
                <translate>Unlink the repository</translate>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { GitLabRepository } from "../type";
import { createDropdown } from "tlp";
import { namespace } from "vuex-class";

const gitlab = namespace("gitlab");

@Component
export default class GitLabAdministration extends Vue {
    @Prop({ required: true })
    readonly is_admin!: boolean;

    @Prop({ required: true })
    readonly repository!: GitLabRepository;

    @gitlab.Action
    readonly showDeleteGitlabRepositoryModal!: (repository: GitLabRepository) => void;

    @gitlab.Action
    readonly showEditAccessTokenGitlabRepositoryModal!: (repository: GitLabRepository) => void;

    @gitlab.Action
    readonly showRegenerateGitlabWebhookModal!: (repository: GitLabRepository) => void;

    @gitlab.Action
    readonly showArtifactClosureModal!: (repository: GitLabRepository) => void;

    @gitlab.Action
    readonly showCreateBranchPrefixModal!: (repository: GitLabRepository) => void;

    mounted() {
        const button_unlink = this.$refs.unlink_gitlab_repository;

        if (button_unlink && button_unlink instanceof Element) {
            button_unlink.addEventListener("click", (event: Event) => {
                event.preventDefault();
                this.showDeleteGitlabRepositoryModal(this.repository);
            });
        }

        const button_edit_access_token = this.$refs.edit_access_token_gitlab_repository;

        if (button_edit_access_token && button_edit_access_token instanceof Element) {
            button_edit_access_token.addEventListener("click", (event: Event) => {
                event.preventDefault();
                this.showEditAccessTokenGitlabRepositoryModal(this.repository);
            });
        }

        const button_regenerate_gitlab_webhook = this.$refs.regenerate_gitlab_webhook;

        if (
            button_regenerate_gitlab_webhook &&
            button_regenerate_gitlab_webhook instanceof Element
        ) {
            button_regenerate_gitlab_webhook.addEventListener("click", (event: Event) => {
                event.preventDefault();
                this.showRegenerateGitlabWebhookModal(this.repository);
            });
        }

        const button_artifact_closure = this.$refs.artifact_closure;

        if (button_artifact_closure && button_artifact_closure instanceof Element) {
            button_artifact_closure.addEventListener("click", (event: Event) => {
                event.preventDefault();
                this.showArtifactClosureModal(this.repository);
            });
        }

        const button_create_branch_prefix = this.$refs.create_branch_prefix;

        if (button_create_branch_prefix && button_create_branch_prefix instanceof Element) {
            button_create_branch_prefix.addEventListener("click", (event: Event) => {
                event.preventDefault();
                this.showCreateBranchPrefixModal(this.repository);
            });
        }

        const button_gitlab_administration = this.$refs.gitlab_administration;
        const dropdown_gitlab_administration = this.$refs.dropdown_gitlab_administration;

        if (
            button_gitlab_administration &&
            button_gitlab_administration instanceof Element &&
            dropdown_gitlab_administration &&
            dropdown_gitlab_administration instanceof Element
        ) {
            button_gitlab_administration.addEventListener("click", (event: Event) => {
                event.preventDefault();
            });
            const dropdownMenu = this.$refs.dropdown_gitlab_administration_menu_options;
            if (dropdownMenu instanceof Element) {
                createDropdown(dropdown_gitlab_administration, {
                    keyboard: false,
                    dropdown_menu: dropdownMenu,
                });
            }
        }
    }
}
</script>
