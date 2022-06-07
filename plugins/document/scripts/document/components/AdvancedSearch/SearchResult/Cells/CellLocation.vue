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
        <template v-for="(parent_item, index) in props.item.parents">
            <router-link
                v-bind:to="getSearchFolderRoute(parent_item)"
                v-bind:key="'link-' + parent_item.id"
                custom
                v-slot="{ navigate, href }"
                data-test="document-cell-location-parent-item-title"
            >
                <a v-bind:href="href" v-on:click="navigate">{{ parent_item.title }}</a>
            </router-link>
            <span
                v-if="props.item.parents.length - 1 > index"
                v-bind:key="'separator-' + parent_item.id"
            >
                /
                <!---->
            </span>
        </template>
    </td>
</template>

<script setup lang="ts">
import type { ItemSearchResult } from "../../../../type";
import { useRoute } from "../../../../helpers/use-router";
import type { Dictionary, Route } from "vue-router/types/router";

const props = defineProps<{ item: ItemSearchResult }>();

const route = useRoute();

function getSearchFolderRoute(parent: { id: number }): Route {
    const params: Dictionary<string> = {};
    params.folder_id = String(parent.id);
    return {
        ...route,
        params,
        query: route.query,
    };
}
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>
