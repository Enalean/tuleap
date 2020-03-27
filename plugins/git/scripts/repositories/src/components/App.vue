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
            <action-bar />
            <error-message />
            <git-repository-create />
            <folder-repository-list v-if="isFolderDisplayMode" />
            <repository-list v-else />
            <repository-list-spinner />
            <filter-empty-state />
            <no-repository-empty-state />
        </div>
    </div>
</template>
<script>
import { mapGetters } from "vuex";
import store from "../store/index.js";
import GitRepositoryCreate from "./GitRepositoryCreate.vue";
import FilterEmptyState from "./FilterEmptyState.vue";
import GitBreadcrumbs from "./GitBreadcrumbs.vue";
import NoRepositoryEmptyState from "./NoRepositoryEmptyState.vue";
import ActionBar from "./ActionBar.vue";
import RepositoryList from "./RepositoryList.vue";
import ErrorMessage from "./ErrorMessage.vue";
import RepositoryListSpinner from "./RepositoryListSpinner.vue";
import FolderRepositoryList from "./folders/FolderRepositoryList.vue";
import { PROJECT_KEY } from "../constants.js";

export default {
    name: "App",
    store,
    components: {
        ErrorMessage,
        RepositoryList,
        ActionBar,
        NoRepositoryEmptyState,
        FilterEmptyState,
        GitRepositoryCreate,
        GitBreadcrumbs,
        RepositoryListSpinner,
        FolderRepositoryList,
    },
    props: {
        displayMode: String,
    },
    computed: {
        ...mapGetters(["isFolderDisplayMode"]),
    },
    mounted() {
        this.$store.commit("setDisplayMode", this.displayMode);
        this.$store.dispatch("changeRepositories", PROJECT_KEY);
    },
};
</script>
