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
    <div class="tlp-framed">
        <div class="tlp-pane">
            <div class="tlp-pane-container">
                <tuleap-pull-request-title v-bind:pull_request_id="pull_request_id" />
                <changes-tabs />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { provide } from "vue";
import { useRoute } from "vue-router";
import { extractPullRequestIdFromRouteParams } from "../router/pull-request-id-extractor";
import { PULL_REQUEST_ID_KEY } from "../constants";
import ChangesTabs from "./ChangesTabs.vue";

import "@tuleap/plugin-pullrequest-title";

const pull_request_id = extractPullRequestIdFromRouteParams(useRoute().params);

provide(PULL_REQUEST_ID_KEY, pull_request_id);
</script>

<style scoped lang="scss">
.tlp-framed,
.tlp-pane-container {
    display: flex;
    flex: 1;
    flex-direction: column;
}

.tlp-pane {
    flex: 1;
    margin: 0;
}
</style>
