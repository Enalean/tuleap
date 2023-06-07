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
    <div class="tlp-property">
        <label class="tlp-label">
            {{ $gettext("Reviewers") }}
        </label>

        <div
            v-if="reviewer_list !== null"
            data-test="pullrequest-reviewer-info"
            class="pullrequest-reviewer-info"
        >
            <div
                class="tlp-avatar-medium"
                v-for="reviewer in reviewer_list"
                v-bind:key="reviewer.id"
            >
                <img v-bind:src="reviewer.avatar_url" data-test="pullrequest-reviewer-avatar" />
            </div>
            <div
                v-if="reviewer_list.length === 0"
                class="pull-request-reviewers-empty-state"
                data-test="pull-request-reviewers-empty-state"
            >
                {{ $gettext("Nobody is reviewing the pull request") }}
            </div>
            <button
                v-if="can_user_manage_reviewers"
                data-test="edit-reviewers-button"
                class="tlp-button-primary tlp-button-outline pull-request-edit-reviewers-button"
                v-on:click="openModal"
                v-bind:aria-label="$gettext(`Manage pull-request's reviewers`)"
            >
                <i
                    class="tlp-button-icon fa-solid fa-pencil pull-request-edit-reviewers-button-icon"
                    aria-hidden="true"
                ></i>
            </button>
            <pull-request-manage-reviewers-modal
                v-if="is_modal_shown && can_user_manage_reviewers"
                data-test="manage-reviewers-modal"
                v-bind:reviewers_list="reviewer_list"
                v-bind:on_save_callback="on_save_callback"
                v-bind:on_cancel_callback="on_cancel_callback"
            />
        </div>
        <property-skeleton v-if="!reviewer_list" />
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type {
    User,
    ReviewersCollection,
    PullRequest,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import { DISPLAY_TULEAP_API_ERROR, PULL_REQUEST_ID_KEY } from "../../constants";
import PropertySkeleton from "../ReadOnlyInfo/PropertySkeleton.vue";
import { fetchReviewersInfo } from "../../api/tuleap-rest-querier";
import PullRequestManageReviewersModal from "./PullRequestManageReviewersModal.vue";
import { isPullRequestInReview } from "../Actions/merge-status-helper";

const { $gettext } = useGettext();

const pull_request_id = strictInject(PULL_REQUEST_ID_KEY);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);

const props = defineProps<{
    pull_request: PullRequest | null;
}>();

const reviewer_list = ref<ReadonlyArray<User> | null>(null);
const is_modal_shown = ref(false);
const can_user_manage_reviewers = computed(
    () =>
        props.pull_request &&
        props.pull_request.user_can_merge &&
        isPullRequestInReview(props.pull_request)
);

fetchReviewersInfo(pull_request_id).match((reviewers: ReviewersCollection) => {
    reviewer_list.value = reviewers.users;
}, displayTuleapAPIFault);

function openModal(): void {
    is_modal_shown.value = true;
}

const on_save_callback = (reviewers: ReadonlyArray<User>): void => {
    reviewer_list.value = reviewers;
    is_modal_shown.value = false;
};
const on_cancel_callback = (): void => {
    is_modal_shown.value = false;
};
</script>

<style lang="scss">
@use "../../../themes/common";

.pull-request-reviewers-empty-state {
    color: var(--tlp-dark-color);
    font-style: italic;

    + .pull-request-edit-reviewers-button {
        margin: 0 0 0 var(--tlp-small-spacing);
    }
}

.pullrequest-reviewer-info {
    display: flex;
    gap: 4px;
    align-items: center;
}

.pull-request-edit-reviewers-button {
    @include common.edit-button-icon-only;

    .pull-request-edit-reviewers-button-icon {
        margin: 0;
        font-size: 0.65rem;
    }
}
</style>
