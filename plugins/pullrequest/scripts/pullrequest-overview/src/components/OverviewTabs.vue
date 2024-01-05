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
    <nav class="tlp-tabs">
        <router-link
            v-bind:to="{ name: VIEW_OVERVIEW_NAME }"
            class="tlp-tab tlp-tab-active"
            data-test="tab-overview"
            >{{ $gettext("Overview") }}</router-link
        >
        <a
            v-if="is_tab_displayed"
            class="tlp-tab"
            v-bind:href="buildUrlForView('commits')"
            data-test="tab-commits"
            >{{ $gettext("Commits") }}</a
        >
        <a
            v-if="is_tab_displayed"
            class="tlp-tab"
            v-bind:href="buildUrlForView('files')"
            data-test="tab-changes"
            >{{ $gettext("Changes") }}</a
        >
    </nav>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { OVERVIEW_APP_BASE_URL_KEY, PULL_REQUEST_ID_KEY, VIEW_OVERVIEW_NAME } from "../constants";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

const { $gettext } = useGettext();

const base_url: URL = strictInject(OVERVIEW_APP_BASE_URL_KEY);
const pull_request_id: number = strictInject(PULL_REQUEST_ID_KEY);

const props = defineProps<{
    pull_request: PullRequest | null;
}>();

const is_tab_displayed = computed(
    () => props.pull_request !== null && props.pull_request.is_git_reference_broken === false,
);

const buildUrlForView = (view_name: string): string => {
    const view_url = new URL("", base_url.toString());
    view_url.hash = `#/pull-requests/${pull_request_id}/${view_name}`;

    return view_url.toString();
};
</script>
