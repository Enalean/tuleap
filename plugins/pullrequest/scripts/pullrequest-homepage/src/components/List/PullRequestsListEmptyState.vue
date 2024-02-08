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
    <div
        class="empty-state-page"
        v-if="have_too_many_filters"
        data-test="empty-state-no-matching-pull-requests"
    >
        <h1 class="empty-state-title">
            {{ $gettext("No result matched your search") }}
        </h1>
        <p class="empty-state-text">
            {{ $gettext("You may have set too many filters. Try to modify them.") }}
        </p>
    </div>
    <div
        class="empty-state-page"
        v-if="no_open_pull_requests"
        data-test="empty-state-no-open-pull-requests"
    >
        <h1 class="empty-state-title">
            {{ $gettext("There are no open pull-requests") }}
        </h1>
        <p class="empty-state-text">
            {{ $gettext("Try to toggle closed pull-requests display.") }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SHOW_CLOSED_PULL_REQUESTS } from "../../injection-symbols";

const { $gettext } = useGettext();

const are_closed_pull_requests_shown = strictInject(SHOW_CLOSED_PULL_REQUESTS);

const props = defineProps<{
    are_some_filters_defined: boolean;
}>();

const have_too_many_filters = computed(() => {
    return props.are_some_filters_defined;
});

const no_open_pull_requests = computed(() => {
    return !(props.are_some_filters_defined && are_closed_pull_requests_shown);
});
</script>
