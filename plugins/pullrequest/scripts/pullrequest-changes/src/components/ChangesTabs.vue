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
        <a
            class="tlp-tab"
            v-bind:href="buildCommitsTabUrl(base_url, pull_request_id)"
            data-test="tab-commits"
            >{{ $gettext("Commits") }}</a
        >
        <router-link
            v-bind:to="{ name: VIEW_CHANGES_NAME }"
            class="tlp-tab tlp-tab-active"
            data-test="tab-changes"
            >{{ $gettext("Changes") }}</router-link
        >
    </nav>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { buildOverviewTabUrl, buildCommitsTabUrl } from "../router/base-url-builders";
import { CHANGES_APP_BASE_URL_KEY, PULL_REQUEST_ID_KEY, VIEW_CHANGES_NAME } from "../constants";

const { $gettext } = useGettext();

const pull_request_id = strictInject(PULL_REQUEST_ID_KEY);
const base_url = strictInject(CHANGES_APP_BASE_URL_KEY);
</script>
