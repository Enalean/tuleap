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
    <div
        class="pull-requests-homepage-related-to-me-switch"
        v-bind:title="deactivated_switch_message"
    >
        <label
            class="tlp-label pull-requests-homepage-related-to-me-switch-label"
            v-bind:class="{ disabled: has_author_or_reviewer_filter }"
            for="pull-requests-homepage-related-to-me-switch"
            data-test="related-to-me-switch-label"
            >{{ $gettext("Related to me") }}</label
        >
        <div class="tlp-switch tlp-switch-mini">
            <input
                type="checkbox"
                id="pull-requests-homepage-related-to-me-switch"
                class="tlp-switch-checkbox"
                data-test="related-to-me-switch"
                v-model="are_related_pull_requests_shown"
                v-bind:disabled="has_author_or_reviewer_filter"
            />
            <label for="pull-requests-homepage-related-to-me-switch" class="tlp-switch-button">{{
                $gettext("Related to me")
            }}</label>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SHOW_PULL_REQUESTS_RELATED_TO_ME } from "../../../injection-symbols";
import { TYPE_FILTER_REVIEWER } from "../Reviewer/ReviewerFilter";
import { TYPE_FILTER_AUTHOR } from "../Author/AuthorFilter";
import type { StoreListFilters } from "../ListFiltersStore";

const { $gettext } = useGettext();
const are_related_pull_requests_shown = strictInject(SHOW_PULL_REQUESTS_RELATED_TO_ME);

const props = defineProps<{
    filters_store: StoreListFilters;
}>();

const has_author_or_reviewer_filter = computed(
    () =>
        props.filters_store.hasAFilterWithType(TYPE_FILTER_AUTHOR) ||
        props.filters_store.hasAFilterWithType(TYPE_FILTER_REVIEWER),
);

const deactivated_switch_message = computed(() =>
    has_author_or_reviewer_filter.value
        ? $gettext(
              `You cannot activate this filter when an "author" or "reviewer" filter is already defined.`,
          )
        : "",
);
</script>

<style scoped lang="scss">
.pull-requests-homepage-related-to-me-switch-label {
    margin: 0;
    white-space: nowrap;

    &.disabled {
        opacity: 0.5;
    }
}

.pull-requests-homepage-related-to-me-switch {
    display: flex;
    flex-flow: row wrap;
    gap: var(--tlp-small-spacing);
    justify-content: center;
}
</style>
