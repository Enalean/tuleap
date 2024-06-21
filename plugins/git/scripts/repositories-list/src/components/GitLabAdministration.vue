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
            class="tlp-dropdown-menu tlp-dropdown-menu-on-icon gitlab-administration-dropdown"
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
                {{ $gettext("Edit access token") }}
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
                {{ $gettext("Regenerate the GitLab webhook") }}
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
                {{ $gettext("Allow artifact closure") }}
            </div>
            <div class="tlp-dropdown-menu-item" role="menuitem" ref="create_branch_prefix">
                <i
                    class="fas fa-code-branch fa-fw tlp-dropdown-menu-item-icon"
                    aria-hidden="true"
                ></i>
                {{ $gettext("Create branch prefix") }}
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
                {{ $gettext("Unlink the repository") }}
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref } from "vue";
import type { GitLabRepository } from "../type";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { useActions } from "vuex-composition-helpers";

const props = defineProps<{
    is_admin: boolean;
    repository: GitLabRepository;
}>();

const {
    showDeleteGitlabRepositoryModal,
    showEditAccessTokenGitlabRepositoryModal,
    showRegenerateGitlabWebhookModal,
    showArtifactClosureModal,
    showCreateBranchPrefixModal,
} = useActions([
    "showDeleteGitlabRepositoryModal",
    "showEditAccessTokenGitlabRepositoryModal",
    "showRegenerateGitlabWebhookModal",
    "showArtifactClosureModal",
    "showCreateBranchPrefixModal",
]);

const unlink_gitlab_repository = ref();
const edit_access_token_gitlab_repository = ref();
const regenerate_gitlab_webhook = ref();
const artifact_closure = ref();
const create_branch_prefix = ref();
const gitlab_administration = ref();
const dropdown_gitlab_administration = ref();
const dropdown_gitlab_administration_menu_options = ref();

onMounted((): void => {
    const button_unlink = unlink_gitlab_repository.value;

    if (button_unlink && button_unlink instanceof Element) {
        button_unlink.addEventListener("click", (event: Event) => {
            event.preventDefault();
            showDeleteGitlabRepositoryModal(props.repository);
        });
    }

    const button_edit_access_token = edit_access_token_gitlab_repository.value;

    if (button_edit_access_token && button_edit_access_token instanceof Element) {
        button_edit_access_token.addEventListener("click", (event: Event) => {
            event.preventDefault();
            showEditAccessTokenGitlabRepositoryModal(props.repository);
        });
    }

    const button_regenerate_gitlab_webhook = regenerate_gitlab_webhook.value;

    if (button_regenerate_gitlab_webhook && button_regenerate_gitlab_webhook instanceof Element) {
        button_regenerate_gitlab_webhook.addEventListener("click", (event: Event) => {
            event.preventDefault();
            showRegenerateGitlabWebhookModal(props.repository);
        });
    }

    const button_artifact_closure = artifact_closure.value;

    if (button_artifact_closure && button_artifact_closure instanceof Element) {
        button_artifact_closure.addEventListener("click", (event: Event) => {
            event.preventDefault();
            showArtifactClosureModal(props.repository);
        });
    }

    const button_create_branch_prefix = create_branch_prefix.value;

    if (button_create_branch_prefix && button_create_branch_prefix instanceof Element) {
        button_create_branch_prefix.addEventListener("click", (event: Event) => {
            event.preventDefault();
            showCreateBranchPrefixModal(props.repository);
        });
    }

    const button_gitlab_administration = gitlab_administration.value;
    const dropdown_gitlab_administration_value = dropdown_gitlab_administration.value;

    if (
        button_gitlab_administration &&
        button_gitlab_administration instanceof Element &&
        dropdown_gitlab_administration_value &&
        dropdown_gitlab_administration_value instanceof Element
    ) {
        button_gitlab_administration.addEventListener("click", (event: Event) => {
            event.preventDefault();
        });
        const dropdownMenu = dropdown_gitlab_administration_menu_options.value;
        if (dropdownMenu instanceof Element) {
            createDropdown(dropdown_gitlab_administration_value, {
                keyboard: false,
                dropdown_menu: dropdownMenu,
            });
        }
    }
});
</script>
