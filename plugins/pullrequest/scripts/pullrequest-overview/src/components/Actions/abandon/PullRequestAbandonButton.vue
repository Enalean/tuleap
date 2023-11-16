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
    <button
        v-if="is_button_displayed"
        type="button"
        class="tlp-button-danger pull-request-abandon-button"
        v-bind:class="{ 'tlp-button-outline': is_outlined }"
        data-test="abandon-button"
        v-bind:disabled="is_abandon_in_progress"
        v-on:click="proceedPullRequestAbandon()"
    >
        <i
            v-if="!is_abandon_in_progress"
            class="fa-solid fa-trash-can tlp-button-icon"
            aria-hidden="true"
        ></i>
        <i
            v-if="is_abandon_in_progress"
            class="fa-solid fa-circle-notch fa-spin tlp-button-icon"
            aria-hidden="true"
        ></i>
        {{ $gettext("Abandon") }}
    </button>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { isPullRequestBroken, isPullRequestInReview } from "../merge-status-helper";
import { abandonPullRequest } from "../../../api/tuleap-rest-querier";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    DISPLAY_TULEAP_API_ERROR,
    POST_PULL_REQUEST_UPDATE_CALLBACK,
    PULL_REQUEST_ID_KEY,
} from "../../../constants";

const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);
const updatePullRequest = strictInject(POST_PULL_REQUEST_UPDATE_CALLBACK);
const pull_request_id: number = strictInject(PULL_REQUEST_ID_KEY);

const props = defineProps<{
    pull_request: PullRequest;
}>();

const { $gettext } = useGettext();
const is_abandon_in_progress = ref(false);
const is_button_displayed = computed(
    () => isPullRequestInReview(props.pull_request) && props.pull_request.user_can_abandon,
);
const is_outlined = computed(() => !isPullRequestBroken(props.pull_request));

function proceedPullRequestAbandon(): void {
    if (!is_button_displayed.value) {
        return;
    }

    is_abandon_in_progress.value = true;

    abandonPullRequest(pull_request_id)
        .match(updatePullRequest, displayTuleapAPIFault)
        .finally(() => {
            is_abandon_in_progress.value = false;
        });
}
</script>
<style lang="scss">
.pull-request-abandon-button {
    flex: 1 1 50%;
}
</style>
