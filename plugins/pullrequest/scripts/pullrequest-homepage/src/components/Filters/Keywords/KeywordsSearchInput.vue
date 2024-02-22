<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
  -->

<template>
    <input
        type="search"
        class="tlp-search pull-requests-homepage-keyword-search-input"
        data-test="keywords-input"
        v-bind:placeholder="$gettext('Title, description')"
        v-on:keyup="createFilter"
        v-model="keyword"
    />
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useGettext } from "vue3-gettext";
import type { StoreListFilters } from "../ListFiltersStore";
import { KeywordFilterBuilder } from "./KeywordFilter";

const props = defineProps<{
    filters_store: StoreListFilters;
}>();

const { $gettext } = useGettext();

const keyword = ref("");
let nb_keywords_filters = 0;

const createFilter = (event: KeyboardEvent): void => {
    if (event.key !== "Enter" || keyword.value.trim() === "") {
        return;
    }

    const filter = KeywordFilterBuilder($gettext).fromKeyword(
        nb_keywords_filters,
        keyword.value.trim(),
    );
    props.filters_store.storeFilter(filter);

    keyword.value = "";
    nb_keywords_filters++;
};
</script>

<style scoped lang="scss">
.pull-requests-homepage-keyword-search-input {
    width: 300px;
}
</style>
