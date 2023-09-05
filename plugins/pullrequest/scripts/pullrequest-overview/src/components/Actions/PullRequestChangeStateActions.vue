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
    <section
        v-if="is_section_displayed"
        class="tlp-pane-section pullrequest-overview-actions-bottom"
        data-test="state-action-buttons"
    >
        <div v-if="merge_status_warning" class="tlp-alert-warning" data-test="merge-status-warning">
            {{ merge_status_warning }}
        </div>
        <div v-if="merge_status_error" class="tlp-alert-danger" data-test="merge-status-error">
            {{ merge_status_error }}
        </div>
        <div class="pull-request-state-buttons">
            <pull-request-merge-button v-bind:pull_request="props.pull_request" />
            <pull-request-abandon-button v-bind:pull_request="props.pull_request" />
        </div>
        <pull-request-already-merged-state v-bind:pull_request="props.pull_request" />
        <pull-request-abandoned-state v-bind:pull_request="props.pull_request" />
    </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { strictInject } from "@tuleap/vue-strict-inject";
import { ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY } from "../../constants";

import PullRequestMergeButton from "./merge/PullRequestMergeButton.vue";
import PullRequestAbandonButton from "./abandon/PullRequestAbandonButton.vue";
import PullRequestAlreadyMergedState from "./merge/PullRequestAlreadyMergedState.vue";
import PullRequestAbandonedState from "./abandon/PullRequestAbandonedState.vue";
import {
    isPullRequestAlreadyMerged,
    isFastForwardMerge,
    isMergeConflicting,
    isPullRequestAbandoned,
    isPullRequestInReview,
    isSameReferenceMerge,
    isUnknownMerge,
} from "./merge-status-helper";
const { $gettext } = useGettext();
const are_merge_commits_allowed_in_repository = strictInject(
    ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY,
);

const props = defineProps<{
    pull_request: PullRequest;
}>();

const merge_status_warning = computed(() => {
    if (!isPullRequestInReview(props.pull_request)) {
        return "";
    }

    if (isUnknownMerge(props.pull_request)) {
        return $gettext(
            "Pull request mergeability with destination is not determined. You can merge on the command line and push to destination.",
        );
    }

    if (isSameReferenceMerge(props.pull_request)) {
        return $gettext(
            "Pull request cannot be merged because source and destination branches have the very same content.",
        );
    }

    return "";
});

const merge_status_error = computed(() => {
    if (!isPullRequestInReview(props.pull_request)) {
        return "";
    }

    if (!isFastForwardMerge(props.pull_request) && !are_merge_commits_allowed_in_repository) {
        return $gettext(
            "Merge commits are forbidden in the repository configuration (fast-forward only). Please rebase the commit and update the pull request.",
        );
    }

    if (isMergeConflicting(props.pull_request)) {
        return $gettext(
            "Pull request can not be merged automatically due to conflicts with destination. Resolve conflicts on the command line and update the pull request.",
        );
    }

    return "";
});

const is_section_displayed = computed(
    () =>
        isPullRequestAlreadyMerged(props.pull_request) ||
        isPullRequestAbandoned(props.pull_request) ||
        (isPullRequestInReview(props.pull_request) && props.pull_request.user_can_merge),
);
</script>

<style lang="scss">
.pullrequest-overview-actions-bottom {
    display: flex;
    flex-direction: column;
    padding: var(--tlp-x-large-spacing) var(--tlp-medium-spacing) var(--tlp-medium-spacing)
        var(--tlp-medium-spacing);
    border-top: 1px solid var(--tlp-neutral-light-color);
}

.pull-request-state-buttons {
    display: flex;
    gap: var(--tlp-small-spacing);
}
</style>
