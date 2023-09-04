<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
  -
  -->

<template>
    <div class="tlp-modal" role="dialog" aria-labelledby="my-modal-label">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="my-modal-label">
                <i
                    class="far fa-fw fa-times-circle tlp-dropdown-menu-item-icon"
                    aria-hidden="true"
                ></i>
                <translate>Create GitLab branch prefix</translate>
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close_label"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div
            class="tlp-modal-feedback"
            v-if="have_any_rest_error"
            data-test="create-branch-prefix-fail"
        >
            <div class="tlp-alert-danger">
                {{ message_error_rest }}
            </div>
        </div>
        <div class="tlp-modal-body">
            <p v-translate>
                If set, this prefix will be automatically added to the branch name in the create
                GitLab branch action
            </p>
            <div class="tlp-form-element">
                <label class="tlp-label" for="create_branch_prefix_input">
                    <translate>Prefix of the branch name</translate>
                </label>
                <input
                    type="text"
                    id="create_branch_prefix_input"
                    class="tlp-input"
                    v-model="create_branch_prefix"
                />
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-on:click="updateCreateBranchPrefix"
                v-bind:disabled="disabled_button"
                data-test="create-branch-prefix-modal-save-button"
            >
                <i
                    v-if="is_updating_gitlab_repository"
                    class="fas fa-spin fa-circle-notch tlp-button-icon"
                    data-test="create-branch-prefix-modal-icon-spin"
                ></i>
                <translate>Save prefix</translate>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import { Component } from "vue-property-decorator";
import Vue from "vue";
import { namespace } from "vuex-class";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import type { Repository, GitLabRepository } from "../../../type";
import { handleError } from "../../../gitlab/gitlab-error-handler";

const gitlab = namespace("gitlab");

@Component
export default class CreateBranchPrefixModal extends Vue {
    private modal: Modal | null = null;
    is_updating_gitlab_repository = false;
    create_branch_prefix = "";
    message_error_rest = "";

    @gitlab.Action
    readonly updateGitlabRepositoryCreateBranchPrefix!: ({
        integration_id,
        create_branch_prefix,
    }: {
        integration_id: number;
        create_branch_prefix: string;
    }) => Promise<GitLabRepository>;

    @gitlab.State
    readonly create_branch_prefix_repository!: Repository;

    mounted(): void {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-shown", this.onShownModal);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.$store.commit("gitlab/setCreateBranchPrefixModal", this.modal);
    }

    onShownModal(): void {
        this.create_branch_prefix = this.create_branch_prefix_repository.create_branch_prefix;
    }

    reset(): void {
        this.is_updating_gitlab_repository = false;
        this.create_branch_prefix = "";
        this.message_error_rest = "";
    }

    get disabled_button() {
        return this.is_updating_gitlab_repository || this.have_any_rest_error;
    }

    get close_label(): string {
        return this.$gettext("Close");
    }

    get have_any_rest_error(): boolean {
        return this.message_error_rest.length > 0;
    }

    getSuccessMessage(create_branch_prefix: string): string {
        if (create_branch_prefix.length === 0) {
            return this.$gettextInterpolate(
                this.$gettext(
                    "Create branch prefix for integration %{repository} has been successfully cleared.",
                ),
                { repository: this.create_branch_prefix_repository.label },
            );
        }
        return this.$gettextInterpolate(
            this.$gettext(
                "Create branch prefix for integration %{repository} has been successfully updated to '%{branch_prefix}'!",
            ),
            {
                branch_prefix: create_branch_prefix,
                repository: this.create_branch_prefix_repository.label,
            },
        );
    }

    async updateCreateBranchPrefix(event: Event): Promise<void> {
        event.preventDefault();

        try {
            this.is_updating_gitlab_repository = true;

            const updated_integration = await this.updateGitlabRepositoryCreateBranchPrefix({
                integration_id: Number(this.create_branch_prefix_repository.integration_id),
                create_branch_prefix: this.create_branch_prefix,
            });

            if (updated_integration && this.modal) {
                this.modal.hide();
            }

            const success_message = this.getSuccessMessage(
                updated_integration.create_branch_prefix,
            );
            this.$store.commit("setSuccessMessage", success_message);
        } catch (rest_error) {
            this.message_error_rest = await handleError(rest_error, this);
            throw rest_error;
        } finally {
            this.is_updating_gitlab_repository = false;
        }
    }
}
</script>
