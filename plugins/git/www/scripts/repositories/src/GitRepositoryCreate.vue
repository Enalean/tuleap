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
<div role="dialog"
     aria-labelledby="create-repository-modal-title"
     id="create-repository-modal"
     class="tlp-modal"
>
    <div class="tlp-modal-header">
        <h1 class="tlp-modal-title" id="create-repository-modal-title">
            <i class="fa fa-plus tlp-modal-title-icon"></i>
            <translate>Add repository</translate>
        </h1>
        <div class="tlp-modal-close" data-dismiss="modal" v-bind:aria-label="closeLabel">
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
                <i class="fa fa-asterisk"></i></label>
            <input
                    type="text"
                    class="tlp-input"
                    id="repository_name"
                    v-model="repository_name"
                    v-bind:placeholder="placeholder"
                    pattern="[a-zA-Z0-9/_.-]{1,255}"
                    maxlength="255"
                    v-bind:title="repositoryPattern"
            >
            <p class="tlp-text-info">
                <i class="fa fa-info-circle"></i> {{ repositoryPattern }}
                {{ repositoryMaxLenght }}
                {{ repositoryRules }}
            </p>
        </div>
    </div>
    <div class="tlp-modal-footer">
        <button type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
        >
            <translate>Cancel</translate>
        </button>
        <button type="submit" class="tlp-button-primary tlp-modal-action" v-on:click="createRepository()">
            <i class="fa fa-plus tlp-button-icon"></i>
            <translate>Add repository</translate>
        </button>
    </div>
</div>
</template>
<script>
import { postRepository } from "./rest-querier.js";
import { getProjectId } from "./repository-list-presenter.js";

export default {
    name: "GitRepositoryCreate",
    data() {
        return {
            error: "",
            repository_name: ""
        };
    },
    computed: {
        placeholder() {
            return this.$gettext("Repository name");
        },
        repositoryPattern() {
            return this.$gettext("Allowed characters: a-zA-Z0-9/_.-");
        },
        repositoryMaxLenght() {
            return this.$gettext("max length is 255");
        },
        repositoryRules() {
            return this.$gettext(
                'no slashes at the beginning or the end, it also must not finish with ".git'
            );
        },
        closeLabel() {
            return this.$gettext("Close");
        },
    },
    methods: {
        async createRepository() {
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
                    this.error = this.$gettext("You don't have permission to create Git repositories as you are not Git administrator.");
                } else if (error_code === 404) {
                    this.error = this.$gettext("Project not found");
                } else {
                    this.error = this.$gettext(
                        "Something went wrong, please check your network connection"
                    );
                }
            } finally {
                this.is_loading = false;
            }
        }
    }
}
</script>
