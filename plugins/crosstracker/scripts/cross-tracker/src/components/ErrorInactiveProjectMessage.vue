<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
        class="tlp-alert-danger cross-tracker-report-error"
        v-if="has_invalid_trackers && is_user_admin"
    >
        {{ $gettext("The initial query contains trackers from inactive projects:") }}
        <ul>
            <li v-for="tracker of invalid_trackers" v-bind:key="tracker.id">
                {{ tracker.label }} ({{ tracker.project.label }})
            </li>
        </ul>
        {{ $gettext("If you update the query, these trackers will be removed from it.") }}
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { useGettext } from "vue3-gettext";
import type { InvalidTracker } from "../type";
import { IS_USER_ADMIN } from "../injection-symbols";

const props = defineProps<{
    invalid_trackers: ReadonlyArray<InvalidTracker>;
}>();

const { $gettext } = useGettext();
const has_invalid_trackers = computed(() => props.invalid_trackers.length > 0);
const is_user_admin = strictInject(IS_USER_ADMIN);
</script>
