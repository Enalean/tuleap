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
    <nav class="tlp-tabs" data-test="pullrequest-navigation-tabs">
        <a
            class="tlp-tab"
            v-bind:href="buildOverviewTabUrl(base_url, pull_request_id)"
            data-test="tab-overview"
            >{{ $gettext("Overview") }}</a
        >
        <router-link
            v-bind:to="{ name: VIEW_COMMITS_NAME }"
            class="tlp-tab tlp-tab-active"
            data-test="tab-commits"
            >{{ $gettext("Commits") }}</router-link
        >
        <a
            class="tlp-tab"
            v-bind:href="buildFilesTabUrl(base_url, pull_request_id)"
            data-test="tab-changes"
            >{{ $gettext("Changes") }}</a
        >
    </nav>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { COMMITS_APP_BASE_URL_KEY, PULL_REQUEST_ID_KEY, VIEW_COMMITS_NAME } from "../constants";
import { buildFilesTabUrl, buildOverviewTabUrl } from "../router/base-url-builders";

const { $gettext } = useGettext();

const base_url: URL = strictInject(COMMITS_APP_BASE_URL_KEY);
const pull_request_id: number = strictInject(PULL_REQUEST_ID_KEY);
</script>
