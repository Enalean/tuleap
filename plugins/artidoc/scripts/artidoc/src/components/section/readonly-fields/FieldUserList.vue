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
    <label class="tlp-label document-label">{{ user_list_field.label }}</label>
    <p v-if="user_list_field.value.length > 0" class="user-list document-item-list">
        <span
            v-for="user in user_list_field.value"
            v-bind:key="user.display_name"
            class="user-list-item document-list-item-inline document-list-item-with-avatar"
            data-test="user-list-item"
        >
            <div class="tlp-avatar-mini document-avatar-mini">
                <img
                    loading="lazy"
                    v-bind:src="user.avatar_url"
                    data-test="user-list-item-avatar"
                />
            </div>
            {{ user.display_name }}
        </span>
    </p>
    <p v-else class="tlp-property-empty" data-test="empty-state">{{ $gettext("Empty") }}</p>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { ReadonlyFieldUserList } from "@/sections/readonly-fields/ReadonlyFields";

const gettext_provider = useGettext();
const { $gettext } = gettext_provider;
defineProps<{
    user_list_field: ReadonlyFieldUserList;
}>();
</script>

<style scoped lang="scss">
.user-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
}

.user-list-item {
    display: inline-flex;
    align-items: center;
    align-self: center;

    > .tlp-avatar-mini {
        margin: 0 5px 0 0;
    }
}

.user-list-item:not(:last-child)::after {
    content: ",\0000A0";
}
</style>
