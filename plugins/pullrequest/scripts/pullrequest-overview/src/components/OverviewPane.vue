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
    <div class="tlp-framed pull-request-overview-pane">
        <div class="tlp-pane pullrequest-overview-header">
            <div class="tlp-pane-container">
                <pull-request-title v-bind:pull_request="pull_request_info" />
                <overview-tabs />
            </div>
        </div>

        <div class="tlp-pane pullrequest-overview-content-pane">
            <div class="tlp-pane-container pullrequest-overview-section-left">
                <overview-threads
                    v-bind:pull_request_info="pull_request_info"
                    v-bind:pull_request_author="pull_request_author"
                />
            </div>

            <div class="tlp-pane-container pullrequest-overview-section-right">
                <section class="tlp-pane-section pullrequest-overview-actions-top">
                    <pull-request-checkout-button v-bind:pull_request_info="pull_request_info" />
                    <pull-request-edit-title-modal v-bind:pull_request_info="pull_request_info" />
                </section>
                <section class="tlp-pane-section pullrequest-overview-info">
                    <pull-request-author v-bind:pull_request_author="pull_request_author" />
                    <pull-request-creation-date v-bind:pull_request_info="pull_request_info" />
                    <pull-request-reviewer-list v-bind:pull_request="pull_request_info" />
                    <pull-request-stats v-bind:pull_request_info="pull_request_info" />
                    <pull-request-ci-status v-bind:pull_request_info="pull_request_info" />
                    <pull-request-references v-bind:pull_request_info="pull_request_info" />
                    <pull-request-labels v-bind:pull_request="pull_request_info" />
                </section>

                <pull-request-change-state-actions
                    v-if="pull_request_info"
                    v-bind:pull_request="pull_request_info"
                />
            </div>
        </div>
        <pull-request-error-modal v-bind:fault="error" />
    </div>
</template>

<script setup lang="ts">
import { provide, ref } from "vue";
import { useRoute } from "vue-router";
import { fetchPullRequestInfo, fetchUserInfo } from "../api/tuleap-rest-querier";
import { extractPullRequestIdFromRouteParams } from "../helpers/pull-request-id-extractor";
import type { PullRequest, User } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { Fault } from "@tuleap/fault";
import {
    PULL_REQUEST_ID_KEY,
    DISPLAY_TULEAP_API_ERROR,
    POST_PULL_REQUEST_UPDATE_CALLBACK,
} from "../constants";

import PullRequestTitle from "./Title/PullRequestTitle.vue";
import OverviewTabs from "./OverviewTabs.vue";
import OverviewThreads from "./Threads/OverviewThreads.vue";
import PullRequestAuthor from "./ReadOnlyInfo/PullRequestAuthor.vue";
import PullRequestCreationDate from "./ReadOnlyInfo/PullRequestCreationDate.vue";
import PullRequestStats from "./ReadOnlyInfo/PullRequestStats.vue";
import PullRequestCiStatus from "./ReadOnlyInfo/PullRequestCIStatus.vue";
import PullRequestReferences from "./ReadOnlyInfo/PullRequestReferences.vue";
import PullRequestErrorModal from "./Errors/PullRequestErrorModal.vue";
import PullRequestCheckoutButton from "./ReadOnlyInfo/PullRequestCheckoutButton.vue";
import PullRequestEditTitleModal from "./Title/PullRequestEditTitleModal.vue";
import PullRequestReviewerList from "./Reviewers/PullRequestReviewerList.vue";
import PullRequestChangeStateActions from "./Actions/PullRequestChangeStateActions.vue";
import PullRequestLabels from "./Labels/PullRequestLabels.vue";

const route = useRoute();
const pull_request_id = extractPullRequestIdFromRouteParams(route.params);
const pull_request_info = ref<PullRequest | null>(null);
const pull_request_author = ref<User | null>(null);
const error = ref<Fault | null>(null);

provide(PULL_REQUEST_ID_KEY, pull_request_id);
provide(DISPLAY_TULEAP_API_ERROR, (fault: Fault) => handleAPIFault(fault));
provide(POST_PULL_REQUEST_UPDATE_CALLBACK, (pull_request: PullRequest) => {
    pull_request_info.value = pull_request;
});

fetchPullRequestInfo(pull_request_id)
    .andThen((pull_request) => {
        pull_request_info.value = pull_request;

        return fetchUserInfo(pull_request.user_id);
    })
    .match(
        (author) => {
            pull_request_author.value = author;
        },
        (fault) => {
            error.value = fault;
        },
    );

function handleAPIFault(fault: Fault) {
    error.value = fault;
}
</script>

<style lang="scss">
@use "@tuleap/lazybox/style";

.pull-request-overview-pane {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
}

.pullrequest-overview-header {
    margin-bottom: 0;
    border-bottom: 0;
    border-bottom-right-radius: 0;
    border-bottom-left-radius: 0;
}

.pullrequest-overview-content-pane {
    flex: 1 1 auto;
    margin-bottom: 0;
    border-top: 0;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}

.pullrequest-overview-section-left {
    background-color: var(--tlp-background-color-lighter-50);
}

.pullrequest-overview-section-right {
    flex: 0 1 33%;
}

.pullrequest-overview-actions-top {
    display: flex;
    border-bottom: unset;
}

.pullrequest-overview-info {
    padding: var(--tlp-medium-spacing) var(--tlp-medium-spacing) var(--tlp-x-large-spacing)
        var(--tlp-medium-spacing);
    border-bottom: unset;
}
</style>
