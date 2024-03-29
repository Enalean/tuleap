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
    <div class="tlp-framed-vertically">
        <div class="pull-requests-homepage-filters-buttons">
            <div class="pull-requests-homepage-create-filters-controls">
                <keywords-search-input v-bind:filters_store="filters_store" />
                <tuleap-selectors-dropdown
                    v-bind:button_text="$gettext('Add filter')"
                    v-bind:selectors_entries="selectors_entries"
                />
                <button
                    class="tlp-button-outline tlp-button-primary pull-requests-homepage-clear-all-filters-button"
                    v-on:click="filters_store.clearAllFilters()"
                    v-bind:disabled="filters_store.getFilters().value.length === 0"
                    data-test="clear-all-list-filters"
                >
                    {{ $gettext("Clear filters") }}
                </button>
            </div>
            <div class="pull-requests-homepage-display-buttons">
                <closed-pull-requests-filter-switch />
                <related-to-switch v-bind:filters_store="filters_store" />
                <pull-requests-sort-order />
            </div>
        </div>
        <div class="pull-requests-homepage-filters">
            <filter-badge
                v-for="filter in filters_store.getFilters().value"
                v-bind:key="filter.id"
                v-bind:filter="filter"
                v-bind:filters_store="filters_store"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import "@tuleap/plugin-pullrequest-selectors-dropdown";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    DISPLAY_TULEAP_API_ERROR,
    PROJECT_ID,
    REPOSITORY_ID,
    SHOW_PULL_REQUESTS_RELATED_TO_ME,
} from "../../injection-symbols";
import { AuthorSelectorEntry } from "./Author/AuthorSelectorEntry";
import { LabelsSelectorEntry } from "./Labels/LabelsSelectorEntry";
import { TargetBranchSelectorEntry } from "./Branches/TargetBranchSelectorEntry";
import type { StoreListFilters } from "./ListFiltersStore";
import ClosedPullRequestsFilterSwitch from "./Status/ClosedPullRequestsFilterSwitch.vue";
import RelatedToSwitch from "./RelatedToMe/RelatedToMeSwitch.vue";
import FilterBadge from "./FilterBadge.vue";
import PullRequestsSortOrder from "./PullRequestsSortOrder.vue";
import KeywordsSearchInput from "./Keywords/KeywordsSearchInput.vue";
import { ReviewerSelectorEntry } from "./Reviewer/ReviewerSelectorEntry";

const { $gettext } = useGettext();

const repository_id = strictInject(REPOSITORY_ID);
const project_id = strictInject(PROJECT_ID);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);
const are_pull_requests_related_to_me_shown = strictInject(SHOW_PULL_REQUESTS_RELATED_TO_ME);

const props = defineProps<{
    filters_store: StoreListFilters;
}>();

const selectors_entries = [
    AuthorSelectorEntry(
        $gettext,
        displayTuleapAPIFault,
        props.filters_store,
        repository_id,
        are_pull_requests_related_to_me_shown,
    ),
    ReviewerSelectorEntry(
        $gettext,
        displayTuleapAPIFault,
        props.filters_store,
        repository_id,
        are_pull_requests_related_to_me_shown,
    ),
    TargetBranchSelectorEntry($gettext, displayTuleapAPIFault, props.filters_store, repository_id),
    LabelsSelectorEntry($gettext, displayTuleapAPIFault, props.filters_store, project_id),
];
</script>

<style lang="scss">
.pull-requests-homepage-filters-buttons {
    display: flex;
    justify-content: space-between;
    gap: var(--tlp-large-spacing);
}

.pull-requests-homepage-create-filters-controls {
    display: flex;
    gap: var(--tlp-small-spacing);
    flex: 1 1 auto;
}

.pull-requests-homepage-clear-all-filters-button {
    flex-shrink: 0;
}

.pull-request-autocompleter-avatar {
    display: flex;
    gap: 5px;
    align-items: center;
}

.pull-requests-homepage-filters {
    display: flex;
    flex-flow: row wrap;
    margin: var(--tlp-medium-spacing) 0 0 0;
    gap: var(--tlp-small-spacing);
}

.pull-request-homepage-filter-badge {
    display: flex;
    align-items: center;
}

.pull-request-homepage-remove-filter {
    margin: 0 0 0 4px;
    padding: 0;
    border: unset;
    background: unset;
    color: unset;
    text-align: unset;
    cursor: pointer;

    &:hover {
        opacity: 0.5;
    }

    &:focus {
        box-shadow: var(--tlp-shadow-focus);
    }
}

.pull-request-autocompleter-badge-disabled {
    opacity: 0.5;
}

.pull-requests-homepage-display-buttons {
    display: flex;
    flex: 1 1 auto;
    flex-direction: row;
    align-items: center;
    justify-content: flex-end;
    gap: var(--tlp-large-spacing);
}
</style>
