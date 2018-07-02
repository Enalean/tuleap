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
        <git-breadcrumbs v-bind:repositories-administration-url="repositoriesAdministrationUrl"
                         v-bind:repository-list-url="repositoryListUrl"
                         v-bind:repositories-fork-url="repositoriesForkUrl"
        />
        <div class="tlp-framed">
            <h1 v-translate>Git repositories</h1>

            <button type="button" class="tlp-button-primary" v-on:click="show_modal"
                    data-target="create-repository-modal">
                <i class="fa fa-plus tlp-button-icon"></i>
                <translate>Add repository</translate>
            </button>

            <git-repository-create/>

            <div class="empty-page">
                <p class="empty-page-text" v-translate>Project has no Git repositories yet.</p>
            </div>
        </div>
    </div>
</template>
<script>
    import GitRepositoryCreate from './GitRepositoryCreate.vue';
    import GitBreadcrumbs from './GitBreadcrumbs.vue';
    import {modal as tlpModal} from 'tlp';

    export default {
        name: 'GitRepositoriesList',
        props: {
            repositoriesAdministrationUrl: String,
            repositoryListUrl: String,
            repositoriesForkUrl: String
        },
        components: {
            GitRepositoryCreate,
            GitBreadcrumbs
        },
        data() {
            return {
                add_repository_modal: null
            };
        },
        methods: {
            show_modal() {
                this.add_repository_modal.toggle();
            }
        },
        mounted() {
            const modal = document.getElementById("create-repository-modal");
            this.add_repository_modal = tlpModal(modal);
        }
    }
</script>
