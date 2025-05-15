<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -
  -->

<template>
    <div class="tlp-form-element document-header-filter-container">
        <input
            type="search"
            class="tlp-search document-search-box"
            v-bind:placeholder="`${$gettext('Name, description...')}`"
            v-model="search_query"
            data-shortcut-search-document
            v-on:keyup.enter="advancedSearch()"
            data-test="document-search-box"
        />
        <router-link
            v-bind:to="{ name: 'search', params: { folder_id: current_folder.id } }"
            v-bind:title="`${$gettext('Search')}`"
        >
            <a class="document-advanced-link" data-test="document-advanced-link">
                {{ $gettext("Advanced") }}
            </a>
        </router-link>
    </div>
</template>

<script setup lang="ts">
import { useState } from "vuex-composition-helpers";
import type { RootState } from "../../type";
import { ref } from "vue";
import { useRouter } from "../../helpers/use-router";

const router = useRouter();
const { current_folder } = useState<Pick<RootState, "current_folder">>(["current_folder"]);
const search_query = ref("");

function advancedSearch(): void {
    router.push({
        name: "search",
        query: {
            q: search_query.value,
        },
        params: {
            folder_id: String(current_folder.value.id),
        },
    });
}
</script>
