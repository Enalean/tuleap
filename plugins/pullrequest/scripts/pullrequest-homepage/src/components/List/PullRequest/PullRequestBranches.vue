<!--
  - Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
    <span class="tlp-badge-secondary">
        <span data-test="pull-request-source-branch">{{ branch_src }}</span>
        <i class="fa-solid fa-long-arrow-alt-right icon" aria-hidden="true"></i>
        <span data-test="pull-request-destination-branch">{{ branch_dest }}</span>
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { REPOSITORY_ID } from "../../../injection-symbols";

const props = defineProps<{ pull_request: PullRequest }>();

const repo_id = strictInject(REPOSITORY_ID);

const branch_src = computed((): string => {
    const source_repository = props.pull_request.repository;
    const source_branch_name = props.pull_request.branch_src;

    return source_repository.id !== repo_id
        ? `${source_repository.name}:${source_branch_name}`
        : source_branch_name;
});

const branch_dest = computed((): string => {
    const destination_repository = props.pull_request.repository_dest;
    const destination_branch_name = props.pull_request.branch_dest;

    return destination_repository.id !== repo_id
        ? `${destination_repository.name}:${destination_branch_name}`
        : destination_branch_name;
});
</script>

<style scoped lang="scss">
.icon {
    padding: 0 4px;
    font-size: 0.8em;
}
</style>
