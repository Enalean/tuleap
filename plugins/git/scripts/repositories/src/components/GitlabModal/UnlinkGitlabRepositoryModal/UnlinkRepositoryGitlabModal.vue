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
        class="tlp-modal"
        ref="unlink_gitlab_repository_modal"
        data-test="unlink-gitlab-repository-modal-form"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="create-repository-modal-title">
                <i class="fa fa-gitlab tlp-modal-title-icon"></i>
                <translate>Unlink GitLab repository?</translate>
            </h1>
            <div
                class="tlp-modal-close"
                tabindex="0"
                role="button"
                data-dismiss="modal"
                v-bind:aria-label="close_label"
            >
                &times;
            </div>
        </div>
        <div class="tlp-modal-body">
            <div
                class="tlp-alert-danger"
                data-test="gitlab-fail-delete-repository"
                v-if="have_any_rest_error"
            >
                {{ message_error_rest }}
            </div>
            <div
                v-else-if="repository !== null"
                class="git-repository-create-modal-body tlp-text-danger"
            >
                <p
                    v-dompurify-html="confirmation_message"
                    data-test="confirm-unlink-gitlab-message"
                ></p>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                data-test="gitlab-unlink-cancel"
            >
                <translate>Cancel</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                data-test="button-delete-gitlab-repository"
                v-on:click="confirmUnlink"
                v-bind:disabled="disabled_button"
            >
                <i
                    class="fa fa-arrow-right tlp-button-icon"
                    v-bind:class="{ 'fa-spin fa-sync-alt': is_loading }"
                    data-test="icon-spin"
                ></i>
                <translate>Unlink the repository</translate>
            </button>
        </div>
    </div>
</template>

<script>
import { createModal } from "tlp";
import { getProjectId } from "../../../repository-list-presenter";
import { deleteIntegrationGitlab } from "../../../api/rest-querier";

export default {
    name: "UnlinkRepositoryGitlabModal",
    data() {
        return {
            modal: null,
            repository: null,
            message_error_rest: "",
            is_loading: false,
        };
    },
    computed: {
        close_label() {
            return this.$gettext("Close");
        },
        confirmation_message() {
            return this.$gettextInterpolate(
                this.$gettext(
                    "Wow, wait a minute. You are about to unlink the GitLab repository <strong>%{ label }</strong>. Please confirm your action."
                ),
                {
                    label: this.repository.label,
                }
            );
        },
        have_any_rest_error() {
            return this.message_error_rest.length > 0;
        },
        disabled_button() {
            return this.is_loading || this.have_any_rest_error;
        },
        success_message() {
            return this.$gettextInterpolate(
                this.$gettext(
                    "GitLab repository <strong>%{ label }</strong> has been successfully unlinked!"
                ),
                {
                    label: this.repository.label,
                }
            );
        },
    },
    mounted() {
        this.modal = createModal(this.$refs.unlink_gitlab_repository_modal);
        this.modal.addEventListener("tlp-modal-shown", this.onShownModal);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.$store.commit("setUnlinkGitlabRepositoryModal", this.modal);
    },
    methods: {
        onShownModal() {
            this.repository = this.$store.state.unlink_gitlab_repository;
        },
        reset() {
            this.is_loading = false;
            this.message_error_rest = "";
        },
        async confirmUnlink(event) {
            event.preventDefault();
            if (this.have_any_rest_error) {
                return;
            }
            this.is_loading = true;
            try {
                await deleteIntegrationGitlab({
                    repository_id: this.repository.integration_id,
                    project_id: getProjectId(),
                });

                this.$store.commit("removeRepository", this.repository);
                this.$store.commit("setSuccessMessage", this.success_message);
                this.modal.hide();
            } catch (rest_error) {
                this.has_rest_error = true;
                await this.handle_error(rest_error);
            } finally {
                this.is_loading = false;
            }
        },
        async handle_error(rest_error) {
            try {
                const { error } = await rest_error.response.json();
                this.message_error_rest = error.code + " " + error.message;
            } catch (error) {
                this.message_error_rest = this.$gettext("Oops, an error occurred!");
            } finally {
                this.is_loading = false;
            }
        },
    },
};
</script>
