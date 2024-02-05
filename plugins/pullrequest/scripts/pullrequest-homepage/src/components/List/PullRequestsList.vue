<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
    <pull-requests-cards-skeletons v-if="is_loading_pull_requests" />
    <pull-requests-list-empty-state
        v-if="!is_loading_pull_requests && pull_requests.length === 0"
        data-test="empty-state"
    />
    <section class="tlp-pane" v-if="!is_loading_pull_requests && pull_requests.length">
        <div class="tlp-pane-container">
            <section class="tlp-pane-section-for-cards">
                <pull-request-card
                    data-test="pull-request-card"
                    v-for="pull_request of pull_requests"
                    v-bind:key="pull_request.id"
                    v-bind:pull_request="pull_request"
                />
            </section>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import type { Ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { fetchAllPullRequests } from "../../api/tuleap-rest-querier";
import {
    DISPLAY_TULEAP_API_ERROR,
    REPOSITORY_ID,
    SHOW_CLOSED_PULL_REQUESTS,
} from "../../injection-symbols";
import type { StoreListFilters } from "../Filters/ListFiltersStore";
import PullRequestCard from "./PullRequest/PullRequestCard.vue";
import PullRequestsCardsSkeletons from "./PullRequestsCardsSkeletons.vue";
import PullRequestsListEmptyState from "./PullRequestsListEmptyState.vue";

const repository_id = strictInject(REPOSITORY_ID);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);
const are_closed_pull_requests_shown = strictInject(SHOW_CLOSED_PULL_REQUESTS);

const pull_requests: Ref<readonly PullRequest[]> = ref([]);
const is_loading_pull_requests = ref(true);

const props = defineProps<{
    filters_store: StoreListFilters;
}>();

const loadPullRequests = (): void => {
    is_loading_pull_requests.value = true;

    fetchAllPullRequests(
        repository_id,
        props.filters_store.getFilters().value,
        are_closed_pull_requests_shown.value,
    ).match((all_pull_requests) => {
        pull_requests.value = all_pull_requests;

        is_loading_pull_requests.value = false;
    }, displayTuleapAPIFault);
};

watch(
    () => [props.filters_store.getFilters().value.length, are_closed_pull_requests_shown.value],
    loadPullRequests,
    { immediate: true },
);
</script>
