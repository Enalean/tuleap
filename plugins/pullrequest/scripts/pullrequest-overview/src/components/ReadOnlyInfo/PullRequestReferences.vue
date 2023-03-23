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
    <div class="tlp-property">
        <label class="tlp-label">
            {{ $gettext("References") }}
        </label>
        <p
            v-if="props.pull_request_info"
            class="pullrequest-source-reference"
            data-test="pullrequest-source-reference"
        >
            {{ props.pull_request_info.reference_src }}
        </p>
        <property-skeleton v-if="!props.pull_request_info" />
        <span
            v-if="props.pull_request_info"
            class="tlp-badge-secondary"
            data-test="pull-request-source-destination"
        >
            {{ props.pull_request_info.branch_src }}
            <i
                class="fa-solid fa-fw fa-long-arrow-alt-right pull-request-source-destination-icon"
                aria-hidden="true"
            ></i>
            {{ props.pull_request_info.branch_dest }}
        </span>
        <property-skeleton v-if="!props.pull_request_info" />
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import PropertySkeleton from "./PropertySkeleton.vue";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

const { $gettext } = useGettext();

const props = defineProps<{
    pull_request_info: PullRequest | null;
}>();
</script>
<style lang="scss">
.pullrequest-source-reference {
    font-family: var(--tlp-font-family-mono);
}

.pull-request-source-destination-icon {
    font-size: 0.8em;
}
</style>
