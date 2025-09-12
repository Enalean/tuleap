<!--
  - Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
    <div class="timetracking-management-query-displayer">
        <div class="timetracking-management-query-displayer-dates">
            <div class="tlp-property">
                <label class="tlp-label">{{ $gettext("From") }}</label>
                <span data-test="start-date">{{ query.start_date }}</span>
            </div>
            <div class="tlp-property">
                <label class="tlp-label">{{ $gettext("To") }}</label>
                <span data-test="end-date">{{ query.end_date }}</span>
            </div>
            <div class="tlp-property">
                <label class="tlp-label">
                    {{ $gettext("Users") }}
                </label>
                <div
                    class="timetracking-management-query-displayer-avatar"
                    data-test="users-displayer"
                >
                    <div
                        class="tlp-avatar-small"
                        v-for="user in query.users_list"
                        v-bind:key="user.id"
                    >
                        <img v-bind:src="user.avatar_url" data-test="img-avatar" />
                    </div>
                    <div v-if="query.users_list.length === 0">
                        {{ $gettext("No user selected") }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { Query } from "../type";

const { $gettext } = useGettext();

defineProps<{
    query: Query;
}>();
</script>

<style scoped lang="scss">
.timetracking-management-query-displayer {
    padding: var(--tlp-medium-spacing);

    &:hover {
        background-color: var(--tlp-main-color-transparent-90);
        cursor: pointer;
    }
}

.timetracking-management-query-displayer-dates {
    display: flex;
    gap: var(--tlp-medium-spacing);
}

.timetracking-management-query-displayer-avatar {
    display: flex;
    gap: var(--tlp-small-spacing);
}
</style>
