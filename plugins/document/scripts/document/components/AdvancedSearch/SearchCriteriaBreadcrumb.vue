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
        <span class="document-search-breadcrumbs-label" v-translate>Searching in</span>
        <i
            class="fa-solid fa-circle-notch fa-spin"
            aria-hidden="true"
            v-if="is_loading_ascendant_hierarchy"
        ></i>
        <template v-else>
            <template v-for="(folder, index) of current_folder_ascendant_hierarchy">
                <i
                    class="fa-solid fa-chevron-right document-search-breadcrumbs-separator"
                    aria-hidden="true"
                    v-if="index > 0"
                    v-bind:key="'separator-' + folder.id"
                ></i>
                <router-link
                    v-bind:to="getSearchInFolderRoute(folder)"
                    class="document-search-breadcrumbs-crumb tlp-badge-secondary tlp-badge-outline"
                    v-bind:key="'folder-' + folder.id"
                >
                    {{ folder.title }}
                </router-link>
            </template>
            <span class="document-search-breadcrumbs-final-separator">.</span>
            <router-link v-bind:to="getSearchInRootFolderRoute()" v-translate>
                Search in whole project documentation
            </router-link>
            .
        </template>
    </p>
</template>

<script setup lang="ts">
import type { Folder, State } from "../../type";
import type { Route } from "vue-router/types/router";
import { useState } from "vuex-composition-helpers";
import { useRoute } from "../../helpers/use-router";

const route = useRoute();

const { current_folder_ascendant_hierarchy, is_loading_ascendant_hierarchy } = useState<
    Pick<State, "current_folder_ascendant_hierarchy" | "is_loading_ascendant_hierarchy">
>(["current_folder_ascendant_hierarchy", "is_loading_ascendant_hierarchy"]);

function getSearchInFolderRoute(folder: Folder): Route {
    return {
        ...route,
        params: {
            ...route.params,
            folder_id: String(folder.id),
        },
        query: {
            ...route.query,
            offset: "0",
        },
    };
}

function getSearchInRootFolderRoute(): Route {
    return {
        ...route,
        params: {},
    };
}
</script>
