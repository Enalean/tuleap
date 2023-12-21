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
    <div class="pull-request-card-reviewers">
        <div class="pull-request-card-reviewers-overlap-container">
            <div
                v-for="reviewer in pull_request.reviewers"
                v-bind:key="reviewer.id"
                class="tlp-avatar-medium pull-request-card-reviewer"
            >
                <img
                    v-bind:title="reviewer.display_name"
                    v-bind:src="reviewer.avatar_url"
                    class="media-object"
                    aria-hidden="true"
                    data-test="pull-request-card-reviewer-avatar"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

defineProps<{
    pull_request: PullRequest;
}>();
</script>

<style scoped lang="scss">
.pull-request-card-reviewers {
    display: flex;
    flex: 15% 0 0;
    align-items: flex-start;
    padding: 0 var(--tlp-medium-spacing);
}

.pull-request-card-reviewers-overlap-container {
    display: inline-flex;
    flex: 1;
    flex-flow: nowrap;
    align-items: center;
    justify-content: flex-end;
}

.pull-request-card-reviewer {
    $border-width: 4px;

    box-sizing: content-box;
    border: $border-width solid var(--tlp-white-color);

    &:not(:first-child) {
        margin-left: calc(-#{$border-width} * 2);
    }
}
</style>
