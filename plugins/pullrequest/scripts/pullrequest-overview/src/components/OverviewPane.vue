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
    <div class="tlp-framed">
        <div class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header pull-request-header">
                    <h2 v-if="pull_request_info" data-test="pullrequest-title">
                        {{ pull_request_info.title }}
                    </h2>
                    <h2 v-if="pull_request_info === null">
                        <span
                            class="tlp-skeleton-text"
                            data-test="pullrequest-title-skeleton"
                        ></span>
                    </h2>
                </div>

                <overview-tabs />

                <section class="tlp-pane-section pull-request-tab-content">
                    <section id="pull-request-overview">{{ $gettext("Overview") }}</section>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { provide, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { useRoute } from "vue-router";
import OverviewTabs from "./OverviewTabs.vue";
import { fetchPullRequestInfo } from "../api/tuleap-rest-querier";
import type { PullRequestInfo } from "../api/types";
import { PULL_REQUEST_ID_KEY } from "../constants";

const { $gettext } = useGettext();

const route = useRoute();
const pull_request_id = String(route.params.id);
const pull_request_info = ref<PullRequestInfo | null>(null);

provide(PULL_REQUEST_ID_KEY, pull_request_id);

fetchPullRequestInfo(pull_request_id).match(
    (result) => {
        pull_request_info.value = result;
    },
    () => {
        // Do nothing, we don't have a way to display errors yet
    }
);
</script>
