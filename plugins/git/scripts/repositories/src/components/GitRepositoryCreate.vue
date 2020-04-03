<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <form
        role="dialog"
        aria-labelledby="create-repository-modal-title"
        id="create-repository-modal"
        class="tlp-modal"
        ref="create_modal"
        v-on:submit="createRepository"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="create-repository-modal-title">
                <i class="fa fa-plus tlp-modal-title-icon"></i>
                <translate>Add project repository</translate>
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
        <div class="tlp-modal-body git-repository-create-modal-body">
            <div v-if="error.length > 0" class="tlp-alert-danger">
                {{ error }}
            </div>

            <div class="tlp-form-element">
                <label class="tlp-label" for="repository_name">
                    <translate>Repository name</translate>
                    <i class="fa fa-asterisk"></i>
                </label>
                <input
                    type="text"
                    class="tlp-input"
                    id="repository_name"
                    required
                    v-model="repository_name"
                    v-bind:placeholder="placeholder"
                    pattern="[a-zA-Z0-9/_.-]{1,255}"
                    maxlength="255"
                    v-bind:title="repository_pattern"
                    data-test="create_repository_name"
                />
                <p class="tlp-text-info">
                    <i class="fa fa-info-circle"></i>
                    <translate>
                        Allowed characters: a-zA-Z0-9/_.- and max length is 255, no slashes at the
                        beginning or the end, it also must not finish with ".git".
                    </translate>
                </p>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                <translate>Cancel</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="is_loading"
                data-test="create_repository"
            >
                <i
                    class="fa fa-plus tlp-button-icon"
                    v-bind:class="{ 'fa-spin fa-spinner': is_loading }"
                ></i>
                <translate>Add project repository</translate>
            </button>
        </div>
    </form>
</template>
<script>
import { postRepository } from "../api/rest-querier.js";
import { getProjectId } from "../repository-list-presenter.js";
import { modal as tlpModal } from "tlp";

export default {
    name: "GitRepositoryCreate",
    data() {
        return {
            error: "",
            is_loading: false,
            repository_name: "",
        };
    },
    computed: {
        placeholder() {
            return this.$gettext("Repository name");
        },
        repository_pattern() {
            return this.$gettext("Allowed characters: a-zA-Z0-9/_.-");
        },
        close_label() {
            return this.$gettext("Close");
        },
    },
    mounted() {
        const create_modal = tlpModal(this.$refs.create_modal);

        create_modal.addEventListener("tlp-modal-hidden", this.reset);

        this.$store.commit("setAddRepositoryModal", create_modal);
    },
    methods: {
        reset() {
            this.repository_name = "";
            this.error = "";
        },
        async createRepository(event) {
            event.preventDefault();
            this.is_loading = true;
            this.error = "";
            try {
                const repository = await postRepository(getProjectId(), this.repository_name);
                window.location.href = repository.html_url;
            } catch (e) {
                const { error } = await e.response.json();
                const error_code = Number.parseInt(error.code, 10);
                if (error_code === 400) {
                    this.error = this.$gettext(
                        'Repository name is not well formatted or is already used. Allowed characters: a-zA-Z0-9/_.- and max length is 255, no slashes at the beginning or the end, it also must not finish with ".git".'
                    );
                } else if (error_code === 401) {
                    this.error = this.$gettext(
                        "You don't have permission to create Git repositories as you are not Git administrator."
                    );
                } else if (error_code === 404) {
                    this.error = this.$gettext("Project not found");
                } else {
                    this.error = this.$gettext("An error occurred while creating the repository.");
                }
                this.is_loading = false;
            }
        },
    },
};
</script>
