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
    <div v-if="status_info" class="pull-request-already-merged-state">
        <div class="pull-request-already-merged-state-top">
            <i class="fa-solid fa-check fa-xl" aria-hidden="true"></i>
            <div class="tlp-avatar-medium">
                <img
                    v-bind:src="status_info.status_updater.avatar_url"
                    data-test="status-updater-avatar"
                />
            </div>
        </div>
        <div class="pull-request-already-merged-state-bottom">
            <p class="tlp-text-success pull-request-merge-date" data-test="status-merged-date">
                {{ getMergedText() }}
                <pull-request-relative-date
                    data-test="pull-request-merge-date"
                    class="pull-request-merge-date"
                    v-bind:date="status_info.status_date"
                />
                {{ $gettext("by") }}
            </p>
            <p class="tlp-text-success" data-test="status-updater-name">
                {{ status_info.status_updater.display_name }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import PullRequestRelativeDate from "../../ReadOnlyInfo/PullRequestRelativeDate.vue";
import { USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY } from "../../../constants";
import { isPreferenceAbsoluteDateFirst } from "../../../helpers/relative-dates-preference-helper";
import { isPullRequestAlreadyMerged } from "../merge-status-helper";

const { $gettext } = useGettext();
const relative_date_display: RelativeDatesDisplayPreference = strictInject(
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
);

const props = defineProps<{
    pull_request: PullRequest;
}>();

const status_info = computed(() =>
    isPullRequestAlreadyMerged(props.pull_request) ? props.pull_request.status_info : null,
);

const getMergedText = (): string => {
    if (isPreferenceAbsoluteDateFirst(relative_date_display)) {
        return $gettext("Merged on");
    }

    return $gettext("Merged");
};
</script>

<style lang="scss">
.pull-request-already-merged-state,
.pull-request-already-merged-state-bottom {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.pull-request-already-merged-state-top {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 0 var(--tlp-medium-spacing) 0;
    color: var(--tlp-success-color);
    gap: var(--tlp-medium-spacing);
}

.pull-request-merge-date {
    margin: 0;
}

.pull-request-merge-date::after {
    display: none;
}
</style>
