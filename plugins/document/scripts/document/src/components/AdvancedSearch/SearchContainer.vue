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
    <div class="document-search-container">
        <search-header />
        <search-item-modals />
        <div class="document-search-container-criteria-table">
            <search-criteria-panel
                v-bind:query="query"
                v-bind:folder_id="folder_id"
                v-on:advanced-search="advancedSearch"
            />
            <search-result-error v-if="error" v-bind:error="error" />
            <search-result-table
                v-if="can_result_table_be_displayed"
                v-bind:is_loading="is_loading"
                v-bind:results="results"
                v-bind:query="query"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import SearchCriteriaPanel from "./SearchCriteriaPanel.vue";
import SearchResultTable from "./SearchResult/SearchResultTable.vue";
import type { AdvancedSearchParams, SearchResult } from "../../type";
import deepEqual from "fast-deep-equal";
import SearchHeader from "./SearchHeader.vue";
import { searchInFolder } from "../../api/rest-querier";
import SearchResultError from "./SearchResult/SearchResultError.vue";
import { getRouterQueryFromSearchParams } from "../../helpers/get-router-query-from-search-params";
import SearchItemModals from "./SearchItemModals.vue";
import emitter from "../../helpers/emitter";
import type { Ref } from "vue";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { useActions } from "vuex-composition-helpers";
import type { RootActionsRetrieve } from "../../store/actions-retrieve";
import { useRouter, useRoute } from "../../helpers/use-router";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

const router = useRouter();
const route = useRoute();

const props = defineProps<{ query: AdvancedSearchParams; offset: number; folder_id: number }>();

const reduce_help_button_class = "reduce-help-button";
const is_loading = ref(false);
const error: Ref<Error | null> = ref(null);
const results: Ref<SearchResult | null> = ref(null);
let current_route = route.query;

const { loadFolder } = useActions<Pick<RootActionsRetrieve, "loadFolder">>(["loadFolder"]);

onMounted(() => {
    loadFolder(props.folder_id);
    document.body.classList.add(reduce_help_button_class);

    emitter.on("new-item-has-just-been-created", reload);
    emitter.on("item-properties-have-just-been-updated", reload);
    emitter.on("item-permissions-have-just-been-updated", reload);
    emitter.on("item-has-just-been-deleted", reload);
    emitter.on("item-has-just-been-updated", reload);
});

onUnmounted(() => {
    document.body.classList.remove(reduce_help_button_class);

    emitter.off("new-item-has-just-been-created", reload);
    emitter.off("item-properties-have-just-been-updated", reload);
    emitter.off("item-permissions-have-just-been-updated", reload);
    emitter.off("item-has-just-been-deleted", reload);
    emitter.off("item-has-just-been-updated", reload);
});

function reload(): void {
    window.location.reload();
}

watch(
    () => props.query,
    (query: AdvancedSearchParams): void => {
        search(query, props.offset);
    },
    { immediate: true },
);

watch(
    () => props.offset,
    (offset: number): void => {
        search(props.query, offset);
    },
);

watch(
    () => props.folder_id,
    (folder_id: number): void => {
        loadFolder(folder_id);
        search(props.query, props.offset);
    },
);

function search(new_query: AdvancedSearchParams, offset: number): void {
    is_loading.value = true;
    error.value = null;
    results.value = null;

    searchInFolder(props.folder_id, new_query, offset)
        .then((search_results: SearchResult) => {
            results.value = search_results;
        })
        .catch((query_error) => {
            error.value = query_error;
            if (!(query_error instanceof FetchWrapperError)) {
                throw query_error;
            }
        })
        .finally(() => {
            is_loading.value = false;
        });
}

const can_result_table_be_displayed = computed((): boolean => {
    return error.value === null;
});

function advancedSearch(params: AdvancedSearchParams): void {
    const query = getRouterQueryFromSearchParams(params);

    if (deepEqual(current_route, query)) {
        return;
    }

    router.push({
        name: "search",
        params: {
            folder_id: String(props.folder_id),
        },
        query,
    });

    current_route = query;
}
</script>
