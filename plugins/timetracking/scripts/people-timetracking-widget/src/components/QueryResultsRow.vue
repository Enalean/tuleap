<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <tr data-test="user-times">
        <td class="user">
            <div>
                <div class="tlp-avatar-small">
                    <img v-bind:src="user_times.user.avatar_url" loading="lazy" />
                </div>
                <a v-bind:href="user_times.user.user_url">
                    {{ user_times.user.display_name }}
                </a>
            </div>
        </td>
        <td class="tlp-table-cell-numeric" data-test="times">{{ formatMinutes(minutes) }}</td>
        <td class="tlp-table-cell-actions">
            <button
                type="button"
                class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline"
                ref="button"
                v-on:click="open(user_times)"
            >
                <i class="tlp-button-icon fa-solid fa-list" aria-hidden="true"></i>
                {{ $gettext("Details") }}
            </button>
        </td>
    </tr>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { computed } from "vue";
import type { UserTimes } from "../type";
import { formatMinutes } from "@tuleap/plugin-timetracking-time-formatters";
import { strictInject } from "@tuleap/vue-strict-inject";
import { OPEN_MODAL_DETAILS } from "../injection-symbols";

const { $gettext } = useGettext();

const props = defineProps<{
    user_times: UserTimes;
}>();

const minutes = computed(() => props.user_times.times.reduce((sum, time) => sum + time.minutes, 0));

const open = strictInject(OPEN_MODAL_DETAILS);
</script>

<style lang="scss" scoped>
.user {
    white-space: nowrap;
}

.tlp-avatar-small {
    margin: 0 4px 0 0;
}
</style>
