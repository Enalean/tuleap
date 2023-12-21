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
    <div
        class="pull-request-card-labels"
        data-test="pull-request-card-labels"
        v-if="!pull_request.is_git_reference_broken"
    >
        <span
            v-for="label in labels"
            data-test="pull-request-card-label"
            v-bind:key="label.id"
            v-bind:class="{
                [`tlp-badge-${label.color}`]: true,
                'tlp-badge-outline': label.is_outline,
            }"
        >
            <i class="fa-solid fa-tag tlp-badge-icon" aria-hidden="true"></i>
            {{ label.label }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import type { Ref } from "vue";
import type { ProjectLabel, PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { fetchPullRequestLabels } from "../../../api/tuleap-rest-querier";

const props = defineProps<{
    pull_request: PullRequest;
}>();

const labels: Ref<readonly ProjectLabel[]> = ref([]);

if (!props.pull_request.is_git_reference_broken) {
    fetchPullRequestLabels(props.pull_request.id).match(
        (pull_request_labels) => {
            labels.value = pull_request_labels;
        },
        () => {
            // Do nothing, because we don't want to spam the users with X error modals.
        },
    );
}
</script>

<style scoped lang="scss">
.pull-request-card-labels {
    display: flex;
    flex: 25% 0 0;
    gap: 4px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
</style>
