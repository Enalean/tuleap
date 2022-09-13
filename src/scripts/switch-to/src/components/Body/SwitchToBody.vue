<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div
        tabindex="-1"
        class="switch-to-modal-body"
        v-bind:class="{ 'switch-to-modal-body-search-results': filter_value }"
    >
        <global-loading-state v-if="should_global_loading_state_be_displayed" />
        <global-empty-state v-else-if="should_global_empty_state_be_displayed" />
        <template v-else>
            <list-of-projects />
            <list-of-recent-items />
            <search-results v-if="should_search_results_be_displayed" />
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import ListOfProjects from "./Projects/ListOfProjects.vue";
import ListOfRecentItems from "./Items/RecentItems/ListOfRecentItems.vue";
import GlobalEmptyState from "./GlobalEmptyState.vue";
import GlobalLoadingState from "./GlobalLoadingState.vue";
import SearchResults from "./Items/SearchResults/SearchResults.vue";
import { useSwitchToStore } from "../../stores";
import { storeToRefs } from "pinia";

const store = useSwitchToStore();
const { is_loading_history, is_history_loaded, history, projects, filter_value } =
    storeToRefs(store);

const should_global_empty_state_be_displayed = computed((): boolean => {
    if (!is_history_loaded.value) {
        return false;
    }

    if (projects.value.length > 0) {
        return false;
    }

    return history.value.entries.length === 0;
});

const should_global_loading_state_be_displayed = computed((): boolean => {
    if (projects.value.length > 0) {
        return false;
    }

    return is_loading_history.value;
});

const should_search_results_be_displayed = computed((): boolean => {
    return filter_value.value !== "";
});
</script>
