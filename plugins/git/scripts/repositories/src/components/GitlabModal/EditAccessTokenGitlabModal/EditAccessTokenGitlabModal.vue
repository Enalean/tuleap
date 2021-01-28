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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -->

<template>
    <div
        role="dialog"
        aria-labelledby="edit-access-token-gitlab-modal-title"
        class="tlp-modal"
        data-test="edit-access-token-gitlab-modal-form"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title">
                <i class="fas fa-key tlp-modal-title-icon" aria-hidden="true"></i>
                <translate id="edit-access-token-gitlab-modal-title">Edit access token</translate>
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
        <access-token-form-modal
            v-if="display_form_to_edit"
            v-bind:repository="repository"
            v-bind:gitlab_token="gitlab_new_token"
            v-on:on-close-modal="onCloseModal"
            v-on:on-get-new-token-gitlab="onGetNewToken"
        />
        <confirm-replace-token-modal
            v-if="display_confirmation_message"
            v-bind:repository="repository"
            v-bind:gitlab_new_token="gitlab_new_token"
            v-on:on-back-button="onBackToEditToken"
            v-on:on-success-edit-token="onSuccessEditToken"
        />
    </div>
</template>

<script lang="ts">
import { Component } from "vue-property-decorator";
import Vue from "vue";
import { createModal, Modal } from "tlp";
import AccessTokenFormModal from "./AccessTokenFormModal.vue";
import ConfirmReplaceTokenModal from "./ConfirmReplaceTokenModal.vue";
import { Repository } from "../../../type";

@Component({ components: { ConfirmReplaceTokenModal, AccessTokenFormModal } })
export default class EditAccessTokenGitlabModal extends Vue {
    private modal: Modal | null = null;
    private repository: Repository | null = null;
    private gitlab_new_token = "";
    private on_back_to_edit = false;

    get close_label(): string {
        return this.$gettext("Close");
    }

    mounted(): void {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-shown", this.onShownModal);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.$store.commit("setEditAccessTokenGitlabRepositoryModal", this.modal);
    }

    onShownModal(): void {
        this.repository = this.$store.state.edit_access_token_gitlab_repository;
    }

    onCloseModal(): void {
        this.reset();
        if (this.modal) {
            this.modal.hide();
        }
    }

    onBackToEditToken(): void {
        this.on_back_to_edit = true;
    }

    onGetNewToken({ token }: { token: string }): void {
        this.gitlab_new_token = token;
        this.on_back_to_edit = false;
    }

    onSuccessEditToken(): void {
        this.$store.commit("setSuccessMessage", this.success_message);
        this.onCloseModal();
    }

    reset(): void {
        this.repository = null;
        this.gitlab_new_token = "";
        this.on_back_to_edit = false;
    }

    get success_message(): string {
        if (!this.repository || !this.repository.normalized_path) {
            return "";
        }

        return this.$gettextInterpolate(
            this.$gettext("Token of GitLab repository %{ label } has been successfully updated."),
            {
                label: this.repository.normalized_path,
            }
        );
    }

    get display_form_to_edit(): boolean {
        return this.repository !== null && (this.gitlab_new_token === "" || this.on_back_to_edit);
    }

    get display_confirmation_message(): boolean {
        return this.repository !== null && this.gitlab_new_token !== "" && !this.on_back_to_edit;
    }
}
</script>
