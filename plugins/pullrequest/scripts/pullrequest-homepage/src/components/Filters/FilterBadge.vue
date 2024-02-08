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
    <span v-bind:class="badge_classes" data-test="list-filter-badge">
        {{ filter.label }}
        <button
            type="button"
            class="pull-request-homepage-remove-filter"
            v-on:click="filters_store.deleteFilter(filter)"
            v-bind:title="$gettext('Delete this filter')"
            data-test="list-filter-badge-delete-button"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { PullRequestsListFilter } from "./PullRequestsListFilter";
import type { StoreListFilters } from "./ListFiltersStore";
import { isLabel } from "./Labels/LabelsSelectorEntry";

const { $gettext } = useGettext();

const props = defineProps<{
    filter: PullRequestsListFilter;
    filters_store: StoreListFilters;
}>();

const badge_classes = computed(() => ({
    "pull-request-homepage-filter-badge": true,
    "tlp-badge-outline":
        (isLabel(props.filter.value) && props.filter.value.is_outline) ||
        !isLabel(props.filter.value),
    [`tlp-badge-${isLabel(props.filter.value) ? props.filter.value.color : "primary"}`]: true,
}));
</script>
