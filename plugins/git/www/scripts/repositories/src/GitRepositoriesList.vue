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
            <h1><translate>Git repositories</translate></h1>

            <div class="repository-list-actions">
                <button type="button"
                        class="tlp-button-primary git-repository-list-create-repository-button"
                        v-if="is_admin"
                        v-on:click="showModal()"
                >
                    <i class="fa fa-plus tlp-button-icon"></i>
                    <translate>Add repository</translate>
                </button>

                <input
                    class="tlp-search repository-list-filter-input"
                    autocomplete="off"
                    v-bind:placeholder="filter_placeholder"
                    type="search"
                    v-model="repository_filter"
                    v-on:keyup="filterRepositories()"
                    size="30"
                    v-if="repositories.length > 0"
                >
            </div>

            <div v-if="error.length > 0" class="tlp-alert-danger">
                {{ error }}
            </div>

            <git-repository-create ref="create_modal"/>

            <div class="git-repository-list" v-if="! is_loading_initial">
                <git-repository v-for="repository in filtered_repositories"
                                v-bind:repository="repository"
                                v-bind:key="repository.id"
                />
            </div>

            <div class="git-repository-list-loading" v-if="show_spinner"></div>

            <div class="empty-page" v-if="show_empty_state || show_filter_empty_state">
                <div class="empty-page-text"
                   v-if="show_filter_empty_state"
                >
                    <translate>No repository name matching your query has been found.</translate>
                </div>
                <div class="empty-page-text"
                   v-if="show_empty_state"
                >
                    <svg class="empty-page-icon git-repository-list-empty-image"
                         xmlns="http://www.w3.org/2000/svg"
                         width="70"
                         height="80"
                         viewBox="0 0 70 80"
                    >
                        <g fill="none" fill-rule="evenodd">
                            <rect width="64.72" height="74.72" x="2.64" y="2.64" stroke-width="5.28" rx="3.96"/>
                            <rect class="git-repository-list-empty-rect" width="7.292" height="7.273" x="10.208" y="11.636"/>
                            <rect class="git-repository-list-empty-rect" width="7.292" height="7.273" x="10.208" y="27.636"/>
                            <rect class="git-repository-list-empty-rect" width="7.292" height="7.273" x="10.208" y="43.636"/>
                            <rect class="git-repository-list-empty-rect" width="7.292" height="7.273" x="10.208" y="59.636"/>
                            <g stroke-width="3.024" transform="translate(26 15)">
                                <path stroke-linecap="square" d="M6.28571429 11.2060041L6.28571429 39.1304348M26.2857143 19.5652174C25.8215873 24.5967868 22.3930159 27.8576564 16 29.3478261 9.93503221 31.2749039 6.88741316 34.5357734 6.85714286 39.1304348"/>
                                <ellipse cx="6.286" cy="5.978" rx="4.774" ry="4.466"/>
                                <ellipse cx="25.714" cy="14.674" rx="4.774" ry="4.466"/>
                                <ellipse cx="6.286" cy="44.022" rx="4.774" ry="4.466"/>
                            </g>
                        </g>
                    </svg>
                    <p class="empty-page-text" v-translate>There are no repositories in this project</p>
                    <button type="button"
                            class="tlp-button-primary"
                            v-if="is_admin"
                            v-on:click="showModal()"
                    >
                        <i class="fa fa-plus tlp-button-icon"></i>
                        <translate>Add repository</translate>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { modal as tlpModal } from "tlp";
import GitRepositoryCreate from "./GitRepositoryCreate.vue";
import GitBreadcrumbs from "./GitBreadcrumbs.vue";
import GitRepository from "./GitRepository.vue";
import { getRepositoryList } from "./rest-querier.js";
import { getProjectId, getUserIsAdmin } from "./repository-list-presenter.js";

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
            filtered_repositories: [],
            repositories: [],
            repository_filter: "",
            is_loading_initial: true,
            is_loading_next: true,
            error: ""
        };
    },
    methods: {
        showModal() {
            this.add_repository_modal.toggle();
        },
        async getRepositories() {
            try {
                this.repositories = await getRepositoryList(getProjectId(), repositories => {
                    this.filtered_repositories.push(...repositories);
                    this.is_loading_initial = false;
                });
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
                this.is_loading_next = false;
            }
        },
        filterRepositories() {
            this.filtered_repositories = this.repositories.filter(repository => {
                return repository.name.toLowerCase().includes(this.repository_filter.toLowerCase());
            });
        }
    },
    mounted() {
        this.add_repository_modal = tlpModal(this.$refs.create_modal.$el);

        this.getRepositories();
    },
    computed: {
        show_empty_state() {
            return this.repositories.length === 0 && !this.is_loading_initial && !this.error;
        },
        show_filter_empty_state() {
            return (
                this.repositories.length > 0 &&
                this.filtered_repositories.length === 0 &&
                !this.is_loading_initial &&
                !this.error
            );
        },
        is_admin() {
            return getUserIsAdmin();
        },
        filter_placeholder() {
            return this.$gettext("Repository name");
        },
        show_spinner() {
            return this.is_loading_initial || this.is_loading_next;
        }
    }
};
</script>
