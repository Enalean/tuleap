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
            />
        </div>
    </div>
</template>

<script lang="ts">
import { Component, Prop, Vue, Watch } from "vue-property-decorator";
import SearchCriteriaPanel from "./SearchCriteriaPanel.vue";
import SearchResultTable from "./SearchResult/SearchResultTable.vue";
import type { AdvancedSearchParams, SearchResult } from "../../type";
import deepEqual from "fast-deep-equal";
import SearchHeader from "./SearchHeader.vue";
import { searchInFolder } from "../../api/rest-querier";
import { Action } from "vuex-class";
import SearchResultError from "./SearchResult/SearchResultError.vue";
import { isQueryEmpty } from "../../helpers/is-query-empty";
import { getRouterQueryFromSearchParams } from "../../helpers/get-router-query-from-search-params";

@Component({
    components: { SearchResultError, SearchHeader, SearchResultTable, SearchCriteriaPanel },
})
export default class SearchContainer extends Vue {
    @Prop({ required: true })
    readonly query!: AdvancedSearchParams;

    @Prop({ required: true })
    readonly offset!: number;

    @Prop({ required: true })
    readonly folder_id!: number;

    reduce_help_button_class = "reduce-help-button";
    is_loading = false;
    error: Error | null = null;
    results: SearchResult | null = null;

    @Action
    readonly loadFolder!: (item_id: number) => Promise<void>;

    mounted(): void {
        this.loadFolder(this.folder_id);
        document.body.classList.add(this.reduce_help_button_class);
    }

    beforeUnmount(): void {
        document.body.classList.remove(this.reduce_help_button_class);
    }

    @Watch("query", { immediate: true, deep: true })
    searchQuery(query: AdvancedSearchParams): void {
        this.search(query, this.offset);
    }

    @Watch("offset")
    searchOffset(offset: number): void {
        this.search(this.query, offset);
    }

    @Watch("folder_id")
    searchFolder(folder_id: number): void {
        this.loadFolder(folder_id);
        this.search(this.query, this.offset);
    }

    search(new_query: AdvancedSearchParams, offset: number): void {
        if (isQueryEmpty(new_query)) {
            return;
        }

        this.is_loading = true;
        this.error = null;
        this.results = null;

        searchInFolder(this.folder_id, new_query, offset)
            .then((results: SearchResult) => {
                this.results = results;
            })
            .catch((error) => {
                this.error = error;
                throw error;
            })
            .finally(() => {
                this.is_loading = false;
            });
    }

    get can_result_table_be_displayed(): boolean {
        return this.error === null && !this.is_query_empty;
    }

    get is_query_empty(): boolean {
        return isQueryEmpty(this.query);
    }

    advancedSearch(params: AdvancedSearchParams): void {
        const query = getRouterQueryFromSearchParams(params);

        if (deepEqual(this.$route.query, query)) {
            this.searchQuery(params);
            return;
        }

        this.$router.push({
            name: "search",
            params: {
                folder_id: String(this.folder_id),
            },
            query,
        });
    }
}
</script>
