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

            <git-repository-create/>

            <git-repository v-for="repository in repositories"
                            v-bind:repository="repository"
                            v-bind:key="repository.id"/>

            <div class="empty-page" v-if="has_no_repositories">
                <p class="empty-page-text" v-translate>Project has no Git repositories yet.</p>
            </div>
        </div>
    </div>
</template>
<script>
    import GitRepositoryCreate from './GitRepositoryCreate.vue';
    import GitBreadcrumbs from './GitBreadcrumbs.vue';
    import GitRepository from './GitRepository.vue';
    import {getRepositoryList} from './rest-querier.js';
    import {modal as tlpModal} from 'tlp';
    import {getProjectId} from "./repository-list-presenter.js";

    export default {
        name: 'GitRepositoriesList',
        components: {
            GitRepository,
            GitRepositoryCreate,
            GitBreadcrumbs
        },
        data() {
            return {
                add_repository_modal: null,
                repositories: []
            };
        },
        methods: {
            show_modal() {
                this.add_repository_modal.toggle();
            },
            async getRepositories() {
                this.repositories = await getRepositoryList(getProjectId());
            }
        },
        mounted() {
            const modal = document.getElementById("create-repository-modal");
            this.add_repository_modal = tlpModal(modal);

            this.getRepositories();
        },
        computed: {
            has_no_repositories() {
                return this.repositories.length === 0;
            }
        },
    }
</script>
