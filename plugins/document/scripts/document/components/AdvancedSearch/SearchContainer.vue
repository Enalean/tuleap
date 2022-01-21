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
        <search-header />
        <search-criteria-panel v-bind:query="query" v-on:advanced-search="advancedSearch" />
        <search-result-table v-if="can_result_table_be_displayed" />
    </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import SearchCriteriaPanel from "./SearchCriteriaPanel.vue";
import SearchResultTable from "./SearchResultTable.vue";
import type { AdvancedSearchParams } from "../../type";
import deepEqual from "fast-deep-equal";
import SearchHeader from "./SearchHeader.vue";

@Component({
    components: { SearchHeader, SearchResultTable, SearchCriteriaPanel },
})
export default class SearchContainer extends Vue {
    @Prop({ required: false, default: "" })
    readonly query!: string;

    get can_result_table_be_displayed(): boolean {
        return this.query.length !== 0;
    }

    advancedSearch(params: AdvancedSearchParams): void {
        const query = {
            q: params.query,
        };

        if (deepEqual(this.$route.query, query)) {
            return;
        }

        this.$router.push({
            name: "search",
            query: {
                q: params.query,
            },
        });
    }
}
</script>
