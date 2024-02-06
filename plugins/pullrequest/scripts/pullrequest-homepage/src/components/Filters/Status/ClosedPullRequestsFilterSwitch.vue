<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <div class="pull-requests-homepage-open-closed-switch">
        <label
            class="tlp-label pull-requests-homepage-status-switch-label"
            for="pull-requests-homepage-status-switch"
            >{{ $gettext("Show closed") }}</label
        >
        <div class="tlp-switch tlp-switch-mini">
            <input
                type="checkbox"
                id="pull-requests-homepage-status-switch"
                class="tlp-switch-checkbox"
                data-test="status-switch"
                v-model="are_closed_pull_requests_shown"
            />
            <label for="pull-requests-homepage-status-switch" class="tlp-switch-button">{{
                $gettext("Show closed")
            }}</label>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SHOW_CLOSED_PULL_REQUESTS } from "../../../injection-symbols";

const { $gettext } = useGettext();

const are_closed_pull_requests_shown = strictInject(SHOW_CLOSED_PULL_REQUESTS);
</script>

<style scoped lang="scss">
.pull-requests-homepage-status-switch-label {
    margin: 0;
}

.pull-requests-homepage-open-closed-switch {
    display: flex;
    flex-direction: row;
    gap: var(--tlp-small-spacing);
}
</style>
