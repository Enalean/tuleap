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
        <overview-app-header v-bind:pull_request="pull_request_info" />

        <div class="tlp-pane pullrequest-overview-content-pane">
            <div class="tlp-pane-container pullrequest-overview-threads">
                <overview-threads />
            </div>
            <div class="tlp-pane-container pullrequest-overview-info">
                <section class="tlp-pane-section">
                    <pull-request-author v-bind:pull_request_info="pull_request_info" />
                    <pull-request-creation-date v-bind:pull_request_info="pull_request_info" />
                    <pull-request-stats v-bind:pull_request_info="pull_request_info" />
                    <pull-request-ci-status v-bind:pull_request_info="pull_request_info" />
                    <pull-request-references v-bind:pull_request_info="pull_request_info" />
                </section>
            </div>
        </div>
        <pull-request-error-modal v-bind:fault="error" />
    </div>
</template>

<script setup lang="ts">
import { provide, ref } from "vue";
import { useRoute } from "vue-router";
import { fetchPullRequestInfo } from "../api/tuleap-rest-querier";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { Fault } from "@tuleap/fault";
import { PULL_REQUEST_ID_KEY, DISPLAY_TULEAP_API_ERROR } from "../constants";

import OverviewAppHeader from "./OverviewAppHeader.vue";
import OverviewThreads from "./Threads/OverviewThreads.vue";
import PullRequestAuthor from "./ReadOnlyInfo/PullRequestAuthor.vue";
import PullRequestCreationDate from "./ReadOnlyInfo/PullRequestCreationDate.vue";
import PullRequestStats from "./ReadOnlyInfo/PullRequestStats.vue";
import PullRequestCiStatus from "./ReadOnlyInfo/PullRequestCIStatus.vue";
import PullRequestReferences from "./ReadOnlyInfo/PullRequestReferences.vue";
import PullRequestErrorModal from "./Modals/PullRequestErrorModal.vue";

const route = useRoute();
const pull_request_id = String(route.params.id);
const pull_request_info = ref<PullRequest | null>(null);
const error = ref<Fault | null>(null);

provide(PULL_REQUEST_ID_KEY, pull_request_id);
provide(DISPLAY_TULEAP_API_ERROR, (fault: Fault) => handleAPIFault(fault));

fetchPullRequestInfo(pull_request_id).match(
    (result) => {
        pull_request_info.value = result;
    },
    (fault) => {
        error.value = fault;
    }
);

function handleAPIFault(fault: Fault) {
    error.value = fault;
}
</script>

<style lang="scss">
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

.pullrequest-overview-threads {
    background-color: var(--tlp-background-color-lighter-50);
}

.pullrequest-overview-info {
    flex: 0 1 50%;
}
</style>
