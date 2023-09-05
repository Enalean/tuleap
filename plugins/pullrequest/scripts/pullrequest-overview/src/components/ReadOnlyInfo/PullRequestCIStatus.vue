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
            {{ $gettext("Last CI status") }}
        </label>
        <span
            v-if="props.pull_request_info"
            v-bind:class="getBadgeClass()"
            data-test="pullrequest-ci-status-badge"
        >
            <i
                class="fa-solid"
                v-bind:class="getBadgeIconClass()"
                data-test="pullrequest-ci-badge-icon"
            ></i>
            <span class="pullrequest-ci-status" data-test="pullrequest-ci-badge-status-name">
                {{ getBadgeText() }}
                <pull-request-relative-date
                    v-if="props.pull_request_info.last_build_date"
                    v-bind:date="props.pull_request_info.last_build_date"
                    data-test="pullrequest-ci-status-as-relative-date"
                />
            </span>
        </span>
        <property-skeleton v-if="!props.pull_request_info" />
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import { USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY } from "../../constants";
import PropertySkeleton from "./PropertySkeleton.vue";
import PullRequestRelativeDate from "./PullRequestRelativeDate.vue";
import {
    BUILD_STATUS_FAILED,
    BUILD_STATUS_PENDING,
    BUILD_STATUS_SUCCESS,
    BUILD_STATUS_UNKNOWN,
} from "@tuleap/plugin-pullrequest-constants";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { strictInject } from "@tuleap/vue-strict-inject";
import { isPreferenceAbsoluteDateFirst } from "../../helpers/relative-dates-preference-helper";

const { $gettext } = useGettext();

const relative_date_display: RelativeDatesDisplayPreference = strictInject(
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
);

const props = defineProps<{
    pull_request_info: PullRequest | null;
}>();

function getBadgeClass(): string {
    switch (props.pull_request_info?.last_build_status) {
        case BUILD_STATUS_PENDING:
            return "tlp-badge-outline tlp-badge-info";
        case BUILD_STATUS_FAILED:
            return "tlp-badge-danger";
        case BUILD_STATUS_SUCCESS:
            return "tlp-badge-outline tlp-badge-success";
        case BUILD_STATUS_UNKNOWN:
        default:
            return "tlp-badge-warning";
    }
}

function getBadgeIconClass(): string {
    switch (props.pull_request_info?.last_build_status) {
        case BUILD_STATUS_PENDING:
            return "fa-hourglass";
        case BUILD_STATUS_FAILED:
            return "fa-circle-exclamation";
        case BUILD_STATUS_SUCCESS:
            return "fa-circle-check";
        case BUILD_STATUS_UNKNOWN:
        default:
            return "fa-exclamation-triangle";
    }
}

function getBadgeText(): string {
    const last_build_status = props.pull_request_info?.last_build_status;
    if (last_build_status === BUILD_STATUS_PENDING) {
        return $gettext(`Pending since`);
    }

    if (last_build_status === BUILD_STATUS_SUCCESS) {
        if (isPreferenceAbsoluteDateFirst(relative_date_display)) {
            return $gettext(`Success on`);
        }

        return $gettext("Success");
    }

    if (last_build_status === BUILD_STATUS_FAILED) {
        if (isPreferenceAbsoluteDateFirst(relative_date_display)) {
            return $gettext(`Failure on`);
        }

        return $gettext("Failure");
    }

    return $gettext("Unknown");
}
</script>

<style lang="scss">
.pullrequest-ci-status {
    margin: 0 0 0 4px;

    > .pullrequest-relative-date::after {
        display: none;
    }
}
</style>
