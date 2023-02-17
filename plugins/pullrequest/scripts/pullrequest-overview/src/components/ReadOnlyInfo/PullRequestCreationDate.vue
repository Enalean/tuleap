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
            {{ $gettext("Pull request date") }}
        </label>

        <p v-if="props.pull_request_info" data-test="pullrequest-creation-date">
            <pull-request-relative-date
                data-test="pullrequest-creation-date-as-relative-date"
                v-bind:date="props.pull_request_info.creation_date"
            />
        </p>
        <property-skeleton v-if="!props.pull_request_info" />
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import PullRequestRelativeDate from "./PullRequestRelativeDate.vue";
import PropertySkeleton from "./PropertySkeleton.vue";
import type { PullRequestInfo } from "../../api/types";

const { $gettext } = useGettext();

const props = defineProps<{
    pull_request_info: PullRequestInfo | null;
}>();
</script>
