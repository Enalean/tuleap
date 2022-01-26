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
    <div>
        <table class="tlp-table">
            <thead>
                <tr>
                    <th class="tlp-table-cell-numeric" v-translate>Id</th>
                    <th class="document-search-result-icon"></th>
                    <th v-translate>Title</th>
                    <th v-translate>Description</th>
                    <th v-translate>Owner</th>
                    <th v-translate>Update date</th>
                    <th v-translate>Location</th>
                </tr>
            </thead>
            <table-body-skeleton v-if="is_loading" />
            <table-body-results v-else-if="items.length > 0" v-bind:results="items" />
            <table-body-empty v-else />
        </table>
        <search-result-pagination
            v-if="items.length > 0"
            v-bind:from="results.from"
            v-bind:to="results.to"
            v-bind:total="results.total"
            v-bind:limit="limit"
        />
    </div>
</template>
<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import TableBodySkeleton from "./TableBodySkeleton.vue";
import TableBodyEmpty from "./TableBodyEmpty.vue";
import type { ItemSearchResult, SearchResult } from "../../../type";
import { SEARCH_LIMIT } from "../../../type";
import TableBodyResults from "./TableBodyResults.vue";
import SearchResultPagination from "./SearchResultPagination.vue";

@Component({
    components: { SearchResultPagination, TableBodyResults, TableBodyEmpty, TableBodySkeleton },
})
export default class SearchResultTable extends Vue {
    @Prop({ required: true })
    readonly is_loading!: boolean;

    @Prop({ required: true })
    readonly results!: SearchResult | null;

    get limit(): number {
        return SEARCH_LIMIT;
    }

    get items(): ReadonlyArray<ItemSearchResult> {
        return this.results?.items || [];
    }
}
</script>
