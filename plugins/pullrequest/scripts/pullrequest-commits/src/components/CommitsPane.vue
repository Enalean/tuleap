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
                <section
                    class="tlp-pane-section"
                    v-bind:class="{
                        'tlp-pane-section-for-cards-under-tabs':
                            is_loading || has_commits_to_display,
                    }"
                >
                    <commits-cards-skeletons v-if="is_loading" />
                    <template v-if="has_commits_to_display">
                        <commit-card
                            v-for="commit in pull_request_commits"
                            v-bind:key="commit.id"
                            v-bind:commit="commit"
                        />
                    </template>
                    <div
                        v-if="has_no_commits_to_display"
                        class="tlp-alert-warning"
                        data-test="no-commits-warning"
                    >
                        {{ $gettext("It seems that the pull request does not have any commits.") }}
                    </div>
                    <div
                        v-if="loading_error !== null"
                        class="tlp-alert-danger"
                        data-test="error-message"
                    >
                        {{ error_message }}
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, provide, computed } from "vue";
import type { Ref } from "vue";
import { useRoute } from "vue-router";
import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { useGettext } from "vue3-gettext";
import type { PullRequest, PullRequestCommit } from "@tuleap/plugin-pullrequest-rest-api-types";
import { fetchPullRequestCommits, fetchPullRequestInfo } from "../api/rest-querier";
import { extractPullRequestIdFromRouteParams } from "../router/pull-request-id-extractor";
import { PULL_REQUEST_ID_KEY } from "../constants";
import PullRequestTitle from "./PullRequestTitle.vue";
import CommitsTabs from "./CommitsTabs.vue";
import CommitCard from "./commits/CommitCard.vue";
import CommitsCardsSkeletons from "./commits/CommitsCardsSkeletons.vue";

const { $gettext, interpolate } = useGettext();
const route = useRoute();
const pull_request_info = ref<PullRequest | null>(null);
const pull_request_commits = ref<readonly PullRequestCommit[]>([]);

const pull_request_id = extractPullRequestIdFromRouteParams(route.params);
provide(PULL_REQUEST_ID_KEY, pull_request_id);

const is_loading = ref(true);
const loading_error: Ref<Fault | null> = ref(null);
const has_commits_to_display = computed(
    (): boolean =>
        is_loading.value === false &&
        loading_error.value === null &&
        pull_request_commits.value.length > 0,
);

const has_no_commits_to_display = computed(
    (): boolean =>
        is_loading.value === false &&
        loading_error.value === null &&
        pull_request_commits.value.length === 0,
);

const error_message = computed((): string => {
    if (loading_error.value === null) {
        return "";
    }

    return interpolate($gettext("An error occurred: %{fault}"), { fault: loading_error.value });
});

const handleFault = (fault: Fault): void => {
    loading_error.value = fault;
};

Promise.all([
    fetchPullRequestInfo(pull_request_id).match((pull_request): ResultAsync<null, never> => {
        pull_request_info.value = pull_request;
        return okAsync(null);
    }, handleFault),
    fetchPullRequestCommits(pull_request_id).match((commits): ResultAsync<null, never> => {
        pull_request_commits.value = commits;
        return okAsync(null);
    }, handleFault),
]).finally(() => {
    is_loading.value = false;
});
</script>

<style lang="scss">
.vue-commits-mount-point {
    display: flex;
    flex: 1;
    flex-direction: column;
}
</style>

<style scoped lang="scss">
.tlp-framed,
.tlp-pane-container {
    display: flex;
    flex: 1;
    flex-direction: column;
}

.tlp-pane {
    flex: 1;
    margin: 0;
}

.tlp-pane-section-for-cards-under-tabs {
    padding: var(--tlp-medium-spacing) 0;
}
</style>
