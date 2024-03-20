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
            {{ $gettext("References") }}
        </label>
        <p
            v-if="props.pull_request_info"
            class="pullrequest-source-reference"
            data-test="pullrequest-source-reference"
        >
            {{ props.pull_request_info.reference_src }}
        </p>
        <property-skeleton v-if="!props.pull_request_info" />
        <span
            v-if="props.pull_request_info"
            class="tlp-badge-secondary"
            data-test="pull-request-source-destination"
        >
            <span data-test="pull-request-source-branch">{{ branch_src }}</span>
            <i
                class="fa-solid fa-fw fa-long-arrow-alt-right pull-request-source-destination-icon"
                aria-hidden="true"
            ></i>
            <span data-test="pull-request-destination-branch">{{ branch_dest }}</span>
        </span>
        <property-skeleton v-if="!props.pull_request_info" />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CURRENT_REPOSITORY_ID } from "../../constants";
import PropertySkeleton from "./PropertySkeleton.vue";

const { $gettext } = useGettext();

const props = defineProps<{
    pull_request_info: PullRequest | null;
}>();

const repo_id = strictInject(CURRENT_REPOSITORY_ID);

const branch_src = computed((): string => {
    if (!props.pull_request_info) {
        return "";
    }

    const source_repository = props.pull_request_info.repository;
    const source_branch_name = props.pull_request_info.branch_src;

    return source_repository.id !== repo_id
        ? `${source_repository.name}:${source_branch_name}`
        : source_branch_name;
});

const branch_dest = computed((): string => {
    if (!props.pull_request_info) {
        return "";
    }

    const destination_repository = props.pull_request_info.repository_dest;
    const destination_branch_name = props.pull_request_info.branch_dest;

    return destination_repository.id !== repo_id
        ? `${destination_repository.name}:${destination_branch_name}`
        : destination_branch_name;
});
</script>
<style lang="scss">
.pullrequest-source-reference {
    font-family: var(--tlp-font-family-mono);
}

.pull-request-source-destination-icon {
    font-size: 0.8em;
}
</style>
