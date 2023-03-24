<!--
  - Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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
        </div>
        <property-skeleton v-if="!reviewer_list" />
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import PropertySkeleton from "../ReadOnlyInfo/PropertySkeleton.vue";
import { fetchReviewersInfo } from "../../api/tuleap-rest-querier";
import { DISPLAY_TULEAP_API_ERROR, PULL_REQUEST_ID_KEY } from "../../constants";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Ref } from "vue";
import { ref } from "vue";
import type { User, Reviewer } from "@tuleap/plugin-pullrequest-rest-api-types";

const { $gettext } = useGettext();

const pull_request_id = strictInject(PULL_REQUEST_ID_KEY);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);

const reviewer_list: Ref<Array<User> | null> = ref(null);

fetchReviewersInfo(parseInt(pull_request_id, 10)).match(
    (reviewers: Reviewer) => {
        reviewer_list.value = reviewers.users;
    },
    (fault) => {
        displayTuleapAPIFault(fault);
    }
);
</script>

<style lang="scss">
.pull-request-reviewers-empty-state {
    color: var(--tlp-dark-color);
    font-style: italic;
}

.pullrequest-reviewer-info {
    display: flex;
    gap: 4px;
}
</style>
