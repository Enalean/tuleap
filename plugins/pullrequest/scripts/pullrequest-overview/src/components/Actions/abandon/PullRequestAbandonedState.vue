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
    <div v-if="status_info" class="pull-request-abandoned-state">
        <div class="pull-request-abandoned-state-top">
            <i class="fa-solid fa-trash-can fa-xl" aria-hidden="true"></i>
            <div class="tlp-avatar-medium">
                <img
                    v-bind:src="status_info.status_updater.avatar_url"
                    data-test="status-updater-avatar"
                />
            </div>
        </div>
        <div class="pull-request-abandoned-state-bottom">
            <p class="tlp-text-muted pull-request-abandon-date" data-test="status-abandon-date">
                {{ getAbandonedText() }}
                <pull-request-relative-date
                    data-test="pull-request-abandon-date"
                    class="pull-request-abandon-date"
                    v-bind:date="status_info.status_date"
                />
                {{ $gettext("by") }}
            </p>
            <p class="tlp-text-muted" data-test="status-updater-name">
                {{ status_info.status_updater.display_name }}
            </p>
            <button
                v-if="can_user_reopen_pull_request"
                v-on:click="proceedPullRequestReopening()"
                v-bind:disabled="is_reopening_in_progress"
                class="tlp-button-primary tlp-button-outline"
                data-test="pull-request-reopen-button"
            >
                {{ $gettext("Reopen") }}
                <i
                    v-if="is_reopening_in_progress"
                    class="fa-solid fa-circle-notch fa-spin tlp-button-icon-right"
                    aria-hidden="true"
                ></i>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import PullRequestRelativeDate from "../../ReadOnlyInfo/PullRequestRelativeDate.vue";
import {
    DISPLAY_TULEAP_API_ERROR,
    POST_PULL_REQUEST_UPDATE_CALLBACK,
    PULL_REQUEST_ID_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
} from "../../../constants";

import { isPullRequestAbandoned } from "../merge-status-helper";
import { isPreferenceAbsoluteDateFirst } from "../../../helpers/relative-dates-preference-helper";
import { reopenPullRequest } from "../../../api/tuleap-rest-querier";

const { $gettext } = useGettext();
const is_reopening_in_progress = ref(false);

const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);
const updatePullRequest = strictInject(POST_PULL_REQUEST_UPDATE_CALLBACK);
const pull_request_id: number = strictInject(PULL_REQUEST_ID_KEY);
const relative_date_display: RelativeDatesDisplayPreference = strictInject(
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
);

const props = defineProps<{
    pull_request: PullRequest;
}>();

const status_info = computed(() =>
    isPullRequestAbandoned(props.pull_request) ? props.pull_request.status_info : null,
);

const can_user_reopen_pull_request = computed(
    () => props.pull_request && props.pull_request.user_can_reopen,
);

const getAbandonedText = (): string => {
    if (isPreferenceAbsoluteDateFirst(relative_date_display)) {
        return $gettext("Abandoned on");
    }

    return $gettext("Abandoned");
};

function proceedPullRequestReopening(): void {
    if (!can_user_reopen_pull_request.value) {
        return;
    }

    is_reopening_in_progress.value = true;

    reopenPullRequest(pull_request_id)
        .match(updatePullRequest, displayTuleapAPIFault)
        .finally(() => {
            is_reopening_in_progress.value = false;
        });
}
</script>

<style lang="scss">
.pull-request-abandoned-state,
.pull-request-abandoned-state-bottom {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.pull-request-abandoned-state-top {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 0 var(--tlp-medium-spacing) 0;
    color: var(--tlp-dimmed-color);
    gap: var(--tlp-medium-spacing);
}

.pull-request-abandon-date {
    margin: 0;
}

.pull-request-abandon-date::after {
    display: none;
}
</style>
