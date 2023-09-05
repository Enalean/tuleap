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
    <div
        role="dialog"
        aria-labelledby="unlink-gitlab-repository-modal-title"
        id="unlink-gitlab-repository-modal"
        class="tlp-modal tlp-modal-danger"
        data-test="unlink-gitlab-repository-modal-form"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="create-repository-modal-title">
                <translate>Unlink GitLab repository?</translate>
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
        <div class="tlp-modal-body">
            <div
                class="tlp-alert-danger"
                data-test="gitlab-fail-delete-repository"
                v-if="have_any_rest_error"
            >
                {{ message_error_rest }}
            </div>
            <div v-else-if="repository !== null" class="git-repository-create-modal-body">
                <p
                    v-dompurify-html="confirmation_message"
                    data-test="confirm-unlink-gitlab-message"
                ></p>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                data-test="gitlab-unlink-cancel"
            >
                <translate>Cancel</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-danger tlp-modal-action"
                data-test="button-delete-gitlab-repository"
                v-on:click="confirmUnlink"
                v-bind:disabled="disabled_button"
            >
                <i
                    class="fas tlp-button-icon"
                    v-bind:class="{
                        'fa-spin fa-circle-notch': is_loading,
                        'fa-long-arrow-alt-right': !is_loading,
                    }"
                    data-test="icon-spin"
                ></i>
                <translate>Unlink the repository</translate>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { deleteIntegrationGitlab } from "../../../gitlab/gitlab-api-querier";
import { Component } from "vue-property-decorator";
import Vue from "vue";
import type { Repository } from "../../../type";
import { namespace } from "vuex-class";

const gitlab = namespace("gitlab");

@Component
export default class UnlinkRepositoryGitlabModal extends Vue {
    @gitlab.State
    readonly unlink_gitlab_repository!: Repository;

    private modal: Modal | null = null;
    repository: Repository | null = null;
    message_error_rest = "";
    is_loading = false;

    get close_label(): string {
        return this.$gettext("Close");
    }

    get confirmation_message(): string {
        if (!this.repository || !this.repository.normalized_path) {
            return "";
        }

        return this.$gettextInterpolate(
            this.$gettext(
                "Wow, wait a minute. You are about to unlink the GitLab repository %{ label }. Please confirm your action.",
            ),
            {
                label: this.repository.normalized_path,
            },
        );
    }

    get have_any_rest_error(): boolean {
        return this.message_error_rest.length > 0;
    }

    get disabled_button(): boolean {
        return this.is_loading || this.have_any_rest_error;
    }

    get success_message(): string {
        if (!this.repository || !this.repository.normalized_path) {
            return "";
        }

        return this.$gettextInterpolate(
            this.$gettext("GitLab repository %{ label } has been successfully unlinked!"),
            {
                label: this.repository.normalized_path,
            },
        );
    }

    mounted(): void {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-shown", this.onShownModal);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.$store.commit("gitlab/setUnlinkGitlabRepositoryModal", this.modal);
    }

    onShownModal(): void {
        this.repository = this.unlink_gitlab_repository;
    }

    reset(): void {
        this.is_loading = false;
        this.message_error_rest = "";
    }

    async confirmUnlink(event: Event): Promise<void> {
        event.preventDefault();

        if (this.have_any_rest_error) {
            return;
        }

        if (!this.repository) {
            return;
        }

        this.is_loading = true;
        try {
            await deleteIntegrationGitlab({
                integration_id: Number(this.repository.integration_id),
            });

            this.$store.commit("removeRepository", this.repository);
            this.$store.commit("setSuccessMessage", this.success_message);
            if (this.modal) {
                this.modal.hide();
            }
        } catch (rest_error) {
            await this.handleError(rest_error);
        } finally {
            this.is_loading = false;
        }
    }

    async handleError(rest_error: unknown): Promise<void> {
        try {
            if (!(rest_error instanceof FetchWrapperError)) {
                throw rest_error;
            }
            const { error } = await rest_error.response.json();
            this.message_error_rest = error.code + " " + error.message;
        } catch (error) {
            this.message_error_rest = this.$gettext("Oops, an error occurred!");
        } finally {
            this.is_loading = false;
        }
    }
}
</script>
