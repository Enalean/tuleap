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
    <a
        v-bind:href="commit.html_url"
        class="tlp-card tlp-card-selectable commit"
        data-test="pullrequest-commits-list-commit"
    >
        <div class="commit-metadata">
            <commit-author-avatar v-bind:commit="commit" />
            <div>
                <div>{{ commit.title }}</div>
                <div class="commit-metadata-authored">
                    <commit-author-name v-bind:commit="commit" />
                    <commit-date v-bind:commit="commit" />
                </div>
            </div>
        </div>
        <div class="commit-badges">
            <commit-status-badge
                v-if="commit.commit_status !== null"
                v-bind:commit_status="commit.commit_status"
            />
            <commit-short-id v-bind:commit="commit" />
        </div>
    </a>
</template>

<script setup lang="ts">
import type { PullRequestCommit } from "@tuleap/plugin-pullrequest-rest-api-types";

import CommitStatusBadge from "./CommitStatusBadge.vue";
import CommitShortId from "./CommitShortId.vue";
import CommitAuthorName from "./CommitAuthorName.vue";
import CommitDate from "./CommitDate.vue";
import CommitAuthorAvatar from "./CommitAuthorAvatar.vue";

defineProps<{
    commit: PullRequestCommit;
}>();
</script>

<style scoped lang="scss">
.commit {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.commit-metadata {
    display: flex;
    flex: 1 1 auto;
    gap: var(--tlp-small-spacing);
}

.commit-metadata-authored {
    display: flex;
    margin: var(--tlp-small-spacing) 0 0;
    font-size: 14px;
    line-height: 20px;
    gap: var(--tlp-small-spacing);
}

.commit-badges {
    display: flex;
    gap: var(--tlp-small-spacing);
}
</style>
