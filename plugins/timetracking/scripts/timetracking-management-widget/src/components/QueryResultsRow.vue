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
    <tr>
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
        <td class="tlp-table-cell-numeric" data-test="times">{{ hours }}:{{ minutes }}</td>
        <td></td>
    </tr>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { UserTimes } from "../type";

const props = defineProps<{
    user_times: UserTimes;
}>();

const times = computed(() => props.user_times.times.reduce((sum, time) => sum + time.minutes, 0));
const hours = computed(() => Math.floor(times.value / 60));
const minutes = computed(() => String(times.value % 60).padStart(2, "0"));
</script>

<style lang="scss" scoped>
.user {
    white-space: nowrap;
}

.tlp-avatar-small {
    margin: 0 4px 0 0;
}
</style>
