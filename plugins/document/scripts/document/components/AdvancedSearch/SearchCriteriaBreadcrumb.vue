<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  -
  -->

<template>
    <p class="document-search-breadcrumbs">
        <span class="document-search-breadcrumbs-label" v-translate>Searching in:</span>
        <i
            class="fas fa-circle-notch fa-spin"
            aria-hidden="true"
            v-if="is_loading_ascendant_hierarchy"
        ></i>
        <template v-else>
            <template v-for="(folder, index) of current_folder_ascendant_hierarchy">
                <i
                    class="fas fa-chevron-right document-search-breadcrumbs-separator"
                    aria-hidden="true"
                    v-if="index > 0"
                    v-bind:key="'separator-' + folder.id"
                ></i>
                <router-link
                    v-bind:to="getSearchInFolderRoute(folder)"
                    class="tlp-badge-secondary"
                    v-bind:key="'folder-' + folder.id"
                >
                    {{ folder.title }}
                </router-link>
            </template>
            <span class="document-search-breadcrumbs-separator">.</span>
            <router-link v-bind:to="getSearchInRootFolderRoute()" v-translate>
                Search in whole project documentation
            </router-link>
        </template>
    </p>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import { State } from "vuex-class";
import type { Folder } from "../../type";
import type { Route } from "vue-router/types/router";

@Component
export default class SearchCriteriaBreadcrumb extends Vue {
    @State
    readonly current_folder_ascendant_hierarchy!: Array<Folder>;

    @State
    readonly is_loading_ascendant_hierarchy!: boolean;

    getSearchInFolderRoute(folder: Folder): Route {
        return {
            ...this.$route,
            params: {
                ...this.$route.params,
                folder_id: String(folder.id),
            },
            query: {
                ...this.$route.query,
                offset: "0",
            },
        };
    }

    getSearchInRootFolderRoute(): Route {
        return {
            ...this.$route,
            params: {},
        };
    }
}
</script>
