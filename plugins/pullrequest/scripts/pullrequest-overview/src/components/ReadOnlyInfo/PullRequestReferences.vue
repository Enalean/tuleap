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
        <p v-if="props.pull_request_info" data-test="pullrequest-source-reference">
            {{ props.pull_request_info.reference_src }}
        </p>
        <property-skeleton v-if="!props.pull_request_info" />
        <span
            v-if="props.pull_request_info"
            class="tlp-badge-secondary"
            data-test="pull-request-source-destination"
        >
            {{ props.pull_request_info.branch_src }}
            <i class="fa-solid fa-long-arrow-alt-right" aria-hidden="true"></i>
            {{ props.pull_request_info.branch_dest }}
        </span>
        <property-skeleton v-if="!props.pull_request_info" />
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import PropertySkeleton from "./PropertySkeleton.vue";
import type { PullRequestInfo } from "../../api/types";

const { $gettext } = useGettext();

const props = defineProps<{
    pull_request_info: PullRequestInfo | null;
}>();
</script>
