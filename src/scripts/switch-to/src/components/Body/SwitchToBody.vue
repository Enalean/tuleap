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

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import ListOfProjects from "./Projects/ListOfProjects.vue";
import ListOfRecentItems from "./RecentItems/ListOfRecentItems.vue";
import GlobalEmptyState from "./GlobalEmptyState.vue";
import type { Project, UserHistory } from "../../type";
import GlobalLoadingState from "./GlobalLoadingState.vue";
import SearchResults from "./SearchResults/SearchResults.vue";
import { useSwitchToStore } from "../../stores";

@Component({
    components: {
        SearchResults,
        GlobalLoadingState,
        GlobalEmptyState,
        ListOfProjects,
        ListOfRecentItems,
    },
})
export default class SwitchToBody extends Vue {
    get is_loading_history(): boolean {
        return useSwitchToStore().is_loading_history;
    }

    get is_history_loaded(): boolean {
        return useSwitchToStore().is_history_loaded;
    }

    get history(): UserHistory {
        return useSwitchToStore().history;
    }

    get projects(): Project[] {
        return useSwitchToStore().projects;
    }

    get filter_value(): string {
        return useSwitchToStore().filter_value;
    }

    get should_global_empty_state_be_displayed(): boolean {
        if (!this.is_history_loaded) {
            return false;
        }

        if (this.projects.length > 0) {
            return false;
        }

        return this.history.entries.length === 0;
    }

    get should_global_loading_state_be_displayed(): boolean {
        if (this.projects.length > 0) {
            return false;
        }

        return this.is_loading_history;
    }

    get should_search_results_be_displayed(): boolean {
        return this.filter_value !== "";
    }
}
</script>
