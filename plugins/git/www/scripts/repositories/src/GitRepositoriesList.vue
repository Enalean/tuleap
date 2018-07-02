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
    <div>
        <git-breadcrumbs />
        <div class="tlp-framed">
            <h1 v-translate>Git repositories</h1>

            <button type="button" class="tlp-button-primary git-repository-list-create-repository-button" v-on:click="show_modal"
                    data-target="create-repository-modal">
                <i class="fa fa-plus tlp-button-icon"></i>
                <translate>Add repository</translate>
            </button>

            <div class="git-repository-list-loading" v-if="is_loading"></div>

            <div v-if="error.length > 0" class="tlp-alert-danger">
                {{ error }}
            </div>

            <git-repository-create/>

            <div class="git-repository-list" v-if="! is_loading">
                <git-repository v-for="repository in repositories"
                                v-bind:repository="repository"
                                v-bind:key="repository.id"/>
            </div>

            <div class="empty-page" v-if="show_empty_state">
                <p class="empty-page-text" v-translate>Project has no Git repositories yet.</p>
            </div>
        </div>
    </div>
</template>
<script>
import GitRepositoryCreate from "./GitRepositoryCreate.vue";
import GitBreadcrumbs from "./GitBreadcrumbs.vue";
import GitRepository from "./GitRepository.vue";
import { getRepositoryList } from "./rest-querier.js";
import { modal as tlpModal } from "tlp";
import { getProjectId } from "./repository-list-presenter.js";

export default {
    name: "GitRepositoriesList",
    components: {
        GitRepository,
        GitRepositoryCreate,
        GitBreadcrumbs
    },
    data() {
        return {
            add_repository_modal: null,
            repositories: [],
            is_loading: true,
            error: ""
        };
    },
    methods: {
        show_modal() {
            this.add_repository_modal.toggle();
        },
        async getRepositories() {
            try {
                this.repositories = await getRepositoryList(getProjectId());
            } catch (e) {
                const { error } = await e.response.json();
                if (Number.parseInt(error.code, 10) === 404) {
                    this.error = this.$gettext("Git plugin is not activated");
                } else {
                    this.error = this.$gettext(
                        "Something went wrong, please check your network connection"
                    );
                }
            } finally {
                this.is_loading = false;
            }
        }
    },
    mounted() {
        const modal = document.getElementById("create-repository-modal");
        this.add_repository_modal = tlpModal(modal);

        this.getRepositories();
    },
    computed: {
        show_empty_state() {
            return this.repositories.length === 0 && !this.is_loading && !this.error;
        }
    }
};
</script>
