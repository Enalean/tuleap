<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
  -
  -->

<template>
    <td>
        <template v-for="(parent_item, index) in props.item.parents" v-bind:key="parent_item.id">
            <router-link
                v-bind:to="getSearchFolderRoute(parent_item)"
                custom
                v-slot="{ navigate, href }"
            >
                <a v-bind:href="href" v-on:click="navigate">{{ parent_item.title }}</a>
            </router-link>
            <span v-if="props.item.parents.length - 1 > index">
                /
                <!---->
            </span>
        </template>
    </td>
</template>

<script setup lang="ts">
import type { ItemSearchResult } from "../../../../type";
import type { RouteLocationNormalizedLoaded } from "vue-router";
import { useRoute } from "vue-router";

const props = defineProps<{ item: ItemSearchResult }>();

const route = useRoute();

function getSearchFolderRoute(parent: { id: number }): RouteLocationNormalizedLoaded {
    const params: Record<string, string> = {};
    params.folder_id = String(parent.id);
    return {
        ...route,
        params,
        query: route.query,
    };
}
</script>
