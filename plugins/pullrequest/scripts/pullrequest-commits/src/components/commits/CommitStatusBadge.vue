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
    <span v-bind:class="status_badge_classes" data-test="ci-status-badge">
        <i
            class="fa-solid tlp-badge-icon"
            v-bind:class="status_badge_icon_class"
            data-test="ci-badge-icon"
        ></i>
        <span class="commit-ci-status" data-test="ci-badge-status-name">
            {{ badge_text }}
            <commit-relative-date v-bind:date="commit_status.date" data-test="ci-relative-date" />
        </span>
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    type RelativeDatesDisplayPreference,
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
} from "@tuleap/tlp-relative-date";
import {
    COMMIT_BUILD_STATUS_PENDING,
    COMMIT_BUILD_STATUS_SUCCESS,
    COMMIT_BUILD_STATUS_FAILURE,
} from "@tuleap/plugin-pullrequest-constants";
import type { CommitStatus } from "@tuleap/plugin-pullrequest-rest-api-types";
import CommitRelativeDate from "./CommitRelativeDate.vue";
import { USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY } from "../../constants";

const relative_date_display: RelativeDatesDisplayPreference = strictInject(
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
);
const { $gettext } = useGettext();

const props = defineProps<{
    commit_status: CommitStatus;
}>();

const isPreferenceAbsoluteDateFirst = (
    relative_date_display_preference: RelativeDatesDisplayPreference,
): boolean =>
    relative_date_display_preference === PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN ||
    relative_date_display_preference === PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP;

const status_badge_classes = computed((): string => {
    switch (props.commit_status.name) {
        case COMMIT_BUILD_STATUS_PENDING:
            return "tlp-badge-outline tlp-badge-info";
        case COMMIT_BUILD_STATUS_SUCCESS:
            return "tlp-badge-outline tlp-badge-success";
        case COMMIT_BUILD_STATUS_FAILURE:
            return "tlp-badge-danger";
        default:
            return "";
    }
});

const status_badge_icon_class = computed((): string => {
    switch (props.commit_status.name) {
        case COMMIT_BUILD_STATUS_PENDING:
            return "fa-hourglass";
        case COMMIT_BUILD_STATUS_SUCCESS:
            return "fa-circle-check";
        case COMMIT_BUILD_STATUS_FAILURE:
            return "fa-circle-exclamation";
        default:
            return "";
    }
});

const badge_text = computed((): string => {
    switch (props.commit_status.name) {
        case COMMIT_BUILD_STATUS_PENDING:
            return $gettext("Pending since");
        case COMMIT_BUILD_STATUS_SUCCESS:
            return isPreferenceAbsoluteDateFirst(relative_date_display)
                ? $gettext("Success on")
                : $gettext("Success");
        case COMMIT_BUILD_STATUS_FAILURE:
            return isPreferenceAbsoluteDateFirst(relative_date_display)
                ? $gettext("Failure on")
                : $gettext("Failure");
        default:
            return "";
    }
});
</script>

<style scoped lang="scss">
.commit-relative-date::after {
    display: none;
}
</style>
