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
    <div class="document-search-table-container-with-pagination">
        <div class="document-search-table-container" ref="table_container">
            <table class="tlp-table document-search-table">
                <thead>
                    <tr data-test="document-search-table-columns">
                        <template v-for="column of columns" v-bind:key="column.name">
                            <th
                                v-bind:class="th_classes(column)"
                                v-on:click="toggleSort(column)"
                                v-bind:data-test="`sort-${column.name}`"
                            >
                                {{ column.label }}
                                <i
                                    class="fa-solid fa-file-lines tlp-skeleton-icon document-search-result-icon document-search-result-title-header-icon"
                                    aria-hidden="true"
                                    v-if="column.name === 'title'"
                                ></i>
                                <i
                                    class="fa-solid fa-caret-down"
                                    v-if="hasAscSort(column)"
                                    role="img"
                                    v-bind:title="getAscendingSortMessage(column)"
                                ></i>
                                <i
                                    class="fa-solid fa-caret-up"
                                    v-if="hasDescSort(column)"
                                    role="img"
                                    v-bind:title="getDescendingSortMessage(column)"
                                ></i>
                            </th>
                        </template>
                    </tr>
                </thead>
                <table-body-skeleton v-if="is_loading" v-bind:columns="columns" />
                <table-body-results
                    v-else-if="items.length > 0"
                    v-bind:results="items"
                    v-bind:columns="columns"
                />
                <table-body-empty v-else v-bind:nb_columns="nb_columns" />
            </table>
        </div>
        <search-result-pagination
            v-if="items.length > 0"
            v-bind:from="results.from"
            v-bind:to="results.to"
            v-bind:total="results.total"
            v-bind:limit="limit"
        />
    </div>
</template>
<script setup lang="ts">
import TableBodySkeleton from "./TableBodySkeleton.vue";
import TableBodyEmpty from "./TableBodyEmpty.vue";
import type {
    ItemSearchResult,
    SearchResult,
    SearchResultColumnDefinition,
    State,
    AdvancedSearchParams,
} from "../../../type";
import { SEARCH_LIMIT } from "../../../type";
import TableBodyResults from "./TableBodyResults.vue";
import SearchResultPagination from "./SearchResultPagination.vue";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useState } from "vuex-composition-helpers";
import { useRouter } from "../../../helpers/use-router";
import { useGettext } from "vue3-gettext";
import { getRouterQueryFromSearchParams } from "../../../helpers/get-router-query-from-search-params";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SEARCH_COLUMNS } from "../../../configuration-keys";

const { interpolate, $gettext } = useGettext();

const { current_folder } = useState<Pick<State, "current_folder">>(["current_folder"]);

const props = defineProps<{
    is_loading: boolean;
    results: SearchResult | null;
    query: AdvancedSearchParams;
}>();

const limit = ref(SEARCH_LIMIT);

const columns = strictInject(SEARCH_COLUMNS);

const nb_columns = computed((): number => {
    let nb_extra_columns = columns.some((column) => column.name === "title") ? 1 : 0;

    return columns.length + nb_extra_columns;
});

const items = computed((): ReadonlyArray<ItemSearchResult> => {
    return props.results?.items || [];
});

const router = useRouter();

function hasAscSort(column: SearchResultColumnDefinition): boolean {
    return (
        props.query.sort !== null &&
        props.query.sort.name === column.name &&
        props.query.sort.order === "asc"
    );
}

function hasDescSort(column: SearchResultColumnDefinition): boolean {
    return (
        props.query.sort !== null &&
        props.query.sort.name === column.name &&
        props.query.sort.order === "desc"
    );
}

function toggleSort(column: SearchResultColumnDefinition): void {
    if (!isColumnSortable(column)) {
        return;
    }

    let parameters = props.query.sort;
    let stringify_parameters: Array<string> = [];

    if (parameters && parameters.order === "asc") {
        stringify_parameters.push(column.name + ":desc");
    } else {
        stringify_parameters.push(column.name);
    }

    let route_query = getRouterQueryFromSearchParams(props.query);

    route_query.sort = stringify_parameters.join();

    // the two cal to router replace is a quick fix for following issue:
    // issue: https://github.com/vuejs/vue-router/issues/2624
    // the first replace update the url, so when we'll do the push with parameters
    // the page will be reloaded and ask back to sort
    router.replace({
        name: "search",
    });
    router.push({
        name: "search",
        query: route_query,
        params: {
            folder_id: String(current_folder.value.id),
        },
    });
}

function getAscendingSortMessage(column: SearchResultColumnDefinition): string {
    return interpolate($gettext("Ascending sort on %{label}."), { label: column.label });
}

function getDescendingSortMessage(column: SearchResultColumnDefinition): string {
    return interpolate($gettext("Descending sort on %{label}."), { label: column.label });
}

function th_classes(column: SearchResultColumnDefinition): string[] {
    const classes = ["document-search-result-" + column.name + "-header"];

    if (column.name === "id") {
        classes.push("tlp-table-cell-numeric");
    }

    if (isColumnSortable(column)) {
        classes.push("document-search-column-is-sortable");
    }

    return classes;
}

const table_container = ref<InstanceType<typeof HTMLElement> | null>(null);

function informIsScrolling(): void {
    if (!table_container.value) {
        return;
    }

    if (table_container.value.scrollLeft === 0) {
        table_container.value.classList.remove("document-search-table-container-is-scrolling");
    } else {
        table_container.value.classList.add("document-search-table-container-is-scrolling");
    }
}

function isColumnSortable(column: SearchResultColumnDefinition): boolean {
    return column.name !== "location" && !column.is_multiple_value_allowed;
}
onMounted(() => {
    if (table_container.value) {
        table_container.value.addEventListener("scroll", informIsScrolling, { passive: true });
    }
});

onBeforeUnmount(() => {
    if (table_container.value) {
        table_container.value.removeEventListener("scroll", informIsScrolling);
    }
});
</script>
