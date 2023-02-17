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
    <div class="tlp-framed">
        <overview-app-header v-bind:pull_request="pull_request_info" />

        <div class="tlp-pane pullrequest-overview-content-pane">
            <div class="tlp-pane-container pullrequest-overview-threads">
                <section class="tlp-pane-section">
                    <p class="empty-state-text">Threads of comments section</p>
                </section>
            </div>
            <div class="tlp-pane-container pullrequest-overview-info">
                <section class="tlp-pane-section">
                    <pull-request-creation-date v-bind:pull_request_info="pull_request_info" />
                    <pull-request-stats v-bind:pull_request_info="pull_request_info" />
                    <pull-request-references v-bind:pull_request_info="pull_request_info" />
                </section>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { provide, ref } from "vue";
import { useRoute } from "vue-router";
import { fetchPullRequestInfo } from "../api/tuleap-rest-querier";
import type { PullRequestInfo } from "../api/types";
import { PULL_REQUEST_ID_KEY } from "../constants";

import OverviewAppHeader from "./OverviewAppHeader.vue";
import PullRequestCreationDate from "./ReadOnlyInfo/PullRequestCreationDate.vue";
import PullRequestStats from "./ReadOnlyInfo/PullRequestStats.vue";
import PullRequestReferences from "./ReadOnlyInfo/PullRequestReferences.vue";

const route = useRoute();
const pull_request_id = String(route.params.id);
const pull_request_info = ref<PullRequestInfo | null>(null);

provide(PULL_REQUEST_ID_KEY, pull_request_id);

fetchPullRequestInfo(pull_request_id).match(
    (result) => {
        pull_request_info.value = result;
    },
    () => {
        // Do nothing, we don't have a way to display errors yet
    }
);
</script>

<style lang="scss">
.pullrequest-overview-header {
    margin-bottom: 0;
    border-bottom: 0;
    border-bottom-right-radius: 0;
    border-bottom-left-radius: 0;
}

.pullrequest-overview-content-pane {
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
