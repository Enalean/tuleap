<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
        <div class="tlp-pane">
            <div class="tlp-pane-container">
                <pull-request-title v-bind:pull_request="pull_request_info" />
                <commits-tabs v-bind:pull_request="pull_request_info" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, provide } from "vue";
import { useRoute } from "vue-router";
import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { fetchPullRequestInfo } from "../api/rest-querier";
import { extractPullRequestIdFromRouteParams } from "../router/pull-request-id-extractor";
import { PULL_REQUEST_ID_KEY } from "../constants";
import PullRequestTitle from "./PullRequestTitle.vue";
import CommitsTabs from "./CommitsTabs.vue";

const route = useRoute();
const pull_request_info = ref<PullRequest | null>(null);
const pull_request_id = extractPullRequestIdFromRouteParams(route.params);
provide(PULL_REQUEST_ID_KEY, pull_request_id);

fetchPullRequestInfo(pull_request_id).andThen((pull_request): ResultAsync<null, never> => {
    pull_request_info.value = pull_request;
    return okAsync(null);
});
</script>
