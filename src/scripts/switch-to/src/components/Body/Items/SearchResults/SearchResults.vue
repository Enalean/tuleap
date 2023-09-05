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
    <div
        class="switch-to-search-results"
        v-if="should_be_displayed"
        aria-live="polite"
        v-bind:aria-busy="is_busy ? 'true' : 'false'"
    >
        <h2 class="tlp-modal-subtitle switch-to-modal-body-title">
            {{ $gettext("Search results") }}
            <i
                class="fa-solid fa-circle-question switch-to-search-results-info-icon"
                role="img"
                v-bind:title="$gettext('Search in artifacts only')"
            ></i>
            <i
                class="fa-solid fa-circle-notch fa-spin switch-to-search-results-loading-icon"
                data-test="switch-to-search-results-loading"
                aria-hidden="true"
                v-if="is_busy"
            ></i>
        </h2>
        <search-results-error v-if="is_error" />
        <search-query-too-small v-else-if="is_query_too_small" />
        <search-results-empty v-else-if="is_empty" />
        <search-results-list v-else />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRootStore } from "../../../../stores/root";
import { useFullTextStore } from "../../../../stores/fulltext";
import SearchResultsError from "./SearchResultsError.vue";
import SearchResultsEmpty from "./SearchResultsEmpty.vue";
import SearchResultsList from "./SearchResultsList.vue";
import SearchQueryTooSmall from "./SearchQueryTooSmall.vue";
import { FULLTEXT_MINIMUM_LENGTH_FOR_QUERY } from "../../../../stores/type";

const root_store = useRootStore();
const fulltext_store = useFullTextStore();

const is_query_too_small = computed(
    (): boolean =>
        fulltext_store.fulltext_search_is_available &&
        root_store.keywords.length < FULLTEXT_MINIMUM_LENGTH_FOR_QUERY,
);
const should_be_displayed = computed(
    (): boolean =>
        (fulltext_store.fulltext_search_is_available && !is_query_too_small.value) ||
        (root_store.filtered_history.entries.length === 0 &&
            root_store.filtered_projects.length === 0),
);

const is_busy = computed((): boolean => fulltext_store.fulltext_search_is_loading);
const is_error = computed((): boolean => fulltext_store.fulltext_search_is_error);
const is_empty = computed(
    (): boolean =>
        Object.keys(fulltext_store.fulltext_search_results).length === 0 &&
        !fulltext_store.fulltext_search_is_loading,
);
</script>
