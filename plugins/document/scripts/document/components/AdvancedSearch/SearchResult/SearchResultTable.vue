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
                    <tr>
                        <template v-for="column of columns">
                            <th
                                v-bind:class="th_classes(column)"
                                v-bind:key="'document-search-result-' + column.name + '-header'"
                            >
                                <i
                                    class="fas fa-file-alt tlp-skeleton-icon document-search-result-icon document-search-result-title-header-icon"
                                    aria-hidden="true"
                                    v-if="column.name === 'title'"
                                ></i>
                                {{ column.label }}
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
import type { ItemSearchResult, SearchResult, SearchResultColumnDefinition } from "../../../type";
import { SEARCH_LIMIT } from "../../../type";
import TableBodyResults from "./TableBodyResults.vue";
import SearchResultPagination from "./SearchResultPagination.vue";
import { computed, onBeforeUnmount, onMounted, ref } from "@vue/composition-api";
import { useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";

const props = defineProps<{ is_loading: boolean; results: SearchResult | null }>();

const limit = ref(SEARCH_LIMIT);

const { columns } = useState<Pick<ConfigurationState, "columns">>("configuration", ["columns"]);

const nb_columns = computed((): number => {
    let nb_extra_columns = columns.value.some((column) => column.name === "title") ? 1 : 0;

    return columns.value.length + nb_extra_columns;
});

const items = computed((): ReadonlyArray<ItemSearchResult> => {
    return props.results?.items || [];
});

function th_classes(column: SearchResultColumnDefinition): string[] {
    const classes = ["document-search-result-" + column.name + "-header"];

    if (column.name === "id") {
        classes.push("tlp-table-cell-numeric");
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

<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>
