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
                <translate>Artifact closure</translate>
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
            data-test="update-integration-fail"
        >
            <div class="tlp-alert-danger">
                {{ message_error_rest }}
            </div>
        </div>
        <div class="tlp-modal-body">
            <div class="tlp-form-element">
                <label class="tlp-label tlp-checkbox">
                    <input type="checkbox" v-model="allow_artifact_closure" />
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
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-on:click="updateArtifactClosureValue"
                v-bind:disabled="disabled_button"
                data-test="update-artifact-closure-modal-save-button"
            >
                <i
                    v-if="is_updating_gitlab_repository"
                    class="fas fa-spin fa-circle-notch tlp-button-icon"
                    data-test="update-artifact-closure-modal-icon-spin"
                ></i>
                <translate>Save</translate>
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
export default class ArtifactClosureModal extends Vue {
    private modal: Modal | null = null;
    is_updating_gitlab_repository = false;
    allow_artifact_closure = false;
    message_error_rest = "";

    @gitlab.Action
    readonly updateGitlabRepositoryArtifactClosure!: ({
        integration_id,
        allow_artifact_closure,
    }: {
        integration_id: number;
        allow_artifact_closure: boolean;
    }) => Promise<GitLabRepository>;

    @gitlab.State
    readonly artifact_closure_repository!: Repository;

    mounted(): void {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-shown", this.onShownModal);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.$store.commit("gitlab/setArtifactClosureModal", this.modal);
    }

    onShownModal(): void {
        this.allow_artifact_closure = this.artifact_closure_repository.allow_artifact_closure;
    }

    reset(): void {
        this.is_updating_gitlab_repository = false;
        this.allow_artifact_closure = false;
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

    getSuccessMessage(allow_closure_artifact: boolean): string {
        if (allow_closure_artifact) {
            return this.$gettextInterpolate(
                this.$gettext("Artifact closure is now allowed for '%{repository}'!"),
                { repository: this.artifact_closure_repository.label }
            );
        }
        return this.$gettextInterpolate(
            this.$gettext("Artifact closure is now disabled for '%{repository}'!"),
            { repository: this.artifact_closure_repository.label }
        );
    }

    async updateArtifactClosureValue(event: Event): Promise<void> {
        event.preventDefault();

        try {
            this.is_updating_gitlab_repository = true;

            const updated_integration = await this.updateGitlabRepositoryArtifactClosure({
                integration_id: Number(this.artifact_closure_repository.integration_id),
                allow_artifact_closure: this.allow_artifact_closure,
            });

            if (updated_integration && this.modal) {
                this.modal.hide();
            }

            const success_message = this.getSuccessMessage(
                updated_integration.allow_artifact_closure
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
