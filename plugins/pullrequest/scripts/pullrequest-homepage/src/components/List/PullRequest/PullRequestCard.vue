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
    <a
        v-bind:href="overview_url"
        v-bind:class="{ 'tlp-card-inactive': is_pull_request_closed }"
        class="tlp-card tlp-card-selectable pull-request-homepage-card"
        data-test="pull-request-card"
    >
        <span
            ref="pull_request_title"
            v-dompurify-html="pull_request.title"
            v-bind:class="{ 'tlp-text-muted': is_pull_request_closed }"
            data-test="pull-request-card-title"
        ></span>
        <pull-request-broken-badge v-bind:pull_request="pull_request" />
    </a>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import type { Ref } from "vue";
import { loadTooltips } from "@tuleap/tooltip";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PULL_REQUEST_STATUS_REVIEW } from "@tuleap/plugin-pullrequest-constants";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { buildPullRequestOverviewUrl } from "../../../urls/base-url-builders";
import { BASE_URL } from "../../../injection-symbols";
import PullRequestBrokenBadge from "./PullRequestBrokenBadge.vue";

const props = defineProps<{
    pull_request: PullRequest;
}>();

const base_url = strictInject(BASE_URL);

const pull_request_title: Ref<HTMLElement | undefined> = ref();

const is_pull_request_closed = props.pull_request.status !== PULL_REQUEST_STATUS_REVIEW;
const overview_url = buildPullRequestOverviewUrl(base_url, props.pull_request.id).toString();

onMounted(() => {
    if (!pull_request_title.value) {
        return;
    }

    loadTooltips(pull_request_title.value);
});
</script>

<style scoped lang="scss">
.pull-request-homepage-card {
    display: flex;
    justify-content: space-between;
}
</style>
