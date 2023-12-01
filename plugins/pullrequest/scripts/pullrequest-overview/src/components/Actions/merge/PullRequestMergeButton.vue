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
    <div class="pull-request-merge-button-container" v-if="is_button_displayed">
        <button
            type="button"
            class="tlp-button-success pull-request-merge-button"
            v-bind:class="{
                'tlp-button-outline': is_merge_confirmation_required,
            }"
            data-test="merge-button"
            v-bind:disabled="is_merge_button_disabled"
            v-on:click="merge()"
        >
            <i
                v-if="!is_merge_in_progress"
                class="fa-solid fa-check tlp-button-icon"
                aria-hidden="true"
            ></i>
            <i
                v-if="is_merge_in_progress"
                class="fa-solid fa-circle-notch fa-spin tlp-button-icon"
                aria-hidden="true"
            ></i>
            {{ $gettext("Merge") }}
        </button>
        <pull-request-merge-warning-modal
            v-if="show_confirmation_modal"
            v-bind:pull_request="props.pull_request"
            v-bind:merge_callback="processMerge"
            v-bind:cancel_callback="cancelMerge"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    DISPLAY_TULEAP_API_ERROR,
    ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY,
    POST_PULL_REQUEST_UPDATE_CALLBACK,
    PULL_REQUEST_ID_KEY,
} from "../../../constants";
import PullRequestMergeWarningModal from "../abandon/PullRequestMergeWarningModal.vue";
import { mergePullRequest } from "../../../api/tuleap-rest-querier";
import {
    hasUserPermissionToMerge,
    canPullRequestBeMerged,
    isFastForwardMerge,
    isCIHappy,
    isPullRequestInReview,
    isPullRequestBroken,
} from "../merge-status-helper";

const are_merge_commits_allowed_in_repository = strictInject(
    ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY,
);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);
const updatePullRequest = strictInject(POST_PULL_REQUEST_UPDATE_CALLBACK);
const pull_request_id: number = strictInject(PULL_REQUEST_ID_KEY);

const props = defineProps<{
    pull_request: PullRequest;
}>();

const { $gettext } = useGettext();
const is_merge_in_progress = ref(false);
const show_confirmation_modal = ref(false);

const is_merge_button_disabled = computed(
    () =>
        !canPullRequestBeMerged(props.pull_request, are_merge_commits_allowed_in_repository) ||
        is_merge_in_progress.value,
);

const is_button_displayed = computed(
    () =>
        isPullRequestInReview(props.pull_request) &&
        hasUserPermissionToMerge(props.pull_request) &&
        !isPullRequestBroken(props.pull_request),
);

const is_merge_confirmation_required = computed(
    () => !(isFastForwardMerge(props.pull_request) && isCIHappy(props.pull_request)),
);

const processMerge = () => {
    show_confirmation_modal.value = false;
    is_merge_in_progress.value = true;

    mergePullRequest(pull_request_id)
        .match(updatePullRequest, displayTuleapAPIFault)
        .finally(() => {
            is_merge_in_progress.value = false;
        });
};

const cancelMerge = (): void => {
    show_confirmation_modal.value = false;
};

const merge = (): void => {
    if (!canPullRequestBeMerged(props.pull_request, are_merge_commits_allowed_in_repository)) {
        return;
    }

    if (is_merge_confirmation_required.value === true) {
        show_confirmation_modal.value = true;
        return;
    }

    processMerge();
};
</script>

<style lang="scss">
.pull-request-merge-button-container {
    display: flex;
    flex: 1 1 50%;
}

.pull-request-merge-button {
    flex: 1;
}
</style>
