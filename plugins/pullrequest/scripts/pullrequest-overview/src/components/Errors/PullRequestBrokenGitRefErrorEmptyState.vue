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
    <div class="pull-request-broken-git-ref-empty-state" v-if="pull_request">
        <h1 class="pull-request-broken-git-ref-empty-state-title">{{ $gettext("Oops!") }}</h1>
        <p class="empty-state-text">
            {{ $gettext("It seems your pull-request has failed.") }}
            <span class="pull-request-broken-git-ref-empty-state-action">
                <pull-request-abandon-button v-bind:pull_request="pull_request" />
            </span>
        </p>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import PullRequestAbandonButton from "../Actions/abandon/PullRequestAbandonButton.vue";

defineProps<{
    pull_request: PullRequest | null;
}>();

const { $gettext } = useGettext();
</script>

<style scoped lang="scss">
.pull-request-broken-git-ref-empty-state {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.pull-request-broken-git-ref-empty-state-title {
    margin: 0 0 var(--tlp-medium-spacing) 0;
}

.pull-request-broken-git-ref-empty-state-action {
    display: flex;
    justify-content: center;
    margin: var(--tlp-medium-spacing) 0 0 0;

    > .pull-request-abandon-button {
        flex: unset;
    }
}
</style>
