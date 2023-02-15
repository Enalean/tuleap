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
        <a class="tlp-tab" v-bind:href="buildUrlForView('commits')" data-test="tab-commits">{{
            $gettext("Commits")
        }}</a>
        <a class="tlp-tab" v-bind:href="buildUrlForView('files')" data-test="tab-changes">{{
            $gettext("Changes")
        }}</a>
    </nav>
</template>

<script setup lang="ts">
import { inject } from "vue";
import { useGettext } from "vue3-gettext";
import { useRoute } from "vue-router";
import { OVERVIEW_APP_BASE_URL_KEY, VIEW_OVERVIEW_NAME } from "../constants";

const { $gettext } = useGettext();
const route = useRoute();

const base_url: URL | undefined = inject(OVERVIEW_APP_BASE_URL_KEY);
if (!base_url) {
    throw new Error(`Could not find the injection key '${OVERVIEW_APP_BASE_URL_KEY}'`);
}

const buildUrlForView = (view_name: string) => {
    const view_url = new URL("", base_url.toString());
    view_url.hash = `#/pull-requests/${route.params.id}/${view_name}`;

    return view_url.toString();
};
</script>
