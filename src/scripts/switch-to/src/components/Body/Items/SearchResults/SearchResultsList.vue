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
    <div class="switch-to-search-results-list">
        <item-entry
            v-for="(item, key) of fulltext_search_results"
            v-bind:key="key"
            v-bind:entry="item"
            v-bind:change-focus-callback="changeFocus"
        />
        <div
            v-if="fulltext_search_has_more_results"
            class="switch-to-search-results-list-has-more tlp-text-muted"
        >
            {{ $gettext("There are more results, please refine your search.") }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { useFullTextStore } from "../../../../stores/fulltext";
import ItemEntry from "../ItemEntry.vue";
import type { FocusFromItemPayload } from "../../../../stores/type";
import { storeToRefs } from "pinia";

const fulltext_store = useFullTextStore();
const { fulltext_search_results, fulltext_search_has_more_results } = storeToRefs(fulltext_store);

function changeFocus(payload: FocusFromItemPayload): void {
    fulltext_store.changeFocusFromSearchResult(payload);
}
</script>
