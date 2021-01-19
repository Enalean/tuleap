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
            v-if="repository !== null"
            v-bind:repository="repository"
            v-on:on-close-modal="onCloseModal"
        />
    </div>
</template>

<script lang="ts">
import { Component } from "vue-property-decorator";
import Vue from "vue";
import { createModal, Modal } from "tlp";
import AccessTokenFormModal from "./AccessTokenFormModal.vue";

@Component({ components: { AccessTokenFormModal } })
export default class EditAccessTokenGitlabModal extends Vue {
    private modal: Modal | null = null;
    private repository = null;

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

    reset(): void {
        this.repository = null;
    }
}
</script>
