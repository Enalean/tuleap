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
    <label class="tlp-label document-label">{{ user_groups_list_field.label }}</label>
    <p v-if="user_groups_list_field.value.length > 0" class="user-group-list document-item-list">
        <span
            v-for="user_group in user_groups_list_field.value"
            v-bind:key="user_group.label"
            class="user-group-list-item document-list-item-inline"
            data-test="user-group-list-item"
        >
            {{ user_group.label }}
        </span>
    </p>
    <p v-else class="tlp-property-empty" data-test="empty-state">{{ $gettext("Empty") }}</p>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { ReadonlyFieldUserGroupsList } from "@/sections/readonly-fields/ReadonlyFields";

const gettext_provider = useGettext();
const { $gettext } = gettext_provider;
defineProps<{
    user_groups_list_field: ReadonlyFieldUserGroupsList;
}>();
</script>

<style scoped lang="scss">
.user-group-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
}

.user-group-list-item:not(:last-child)::after {
    content: ",\0000A0";
}
</style>
