<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
        <td>
            <a v-bind:href="repository_permission.url">{{ repository_permission.name }}</a>
        </td>

        <td colspan="2"></td>

        <td>
            <git-permissions-badge
                v-for="group in repository_permission.readers"
                v-bind:key="group.ugroup_name"
                v-bind:is_project_admin="group.is_project_admin"
                v-bind:is_static="group.is_static"
                v-bind:is_custom="group.is_custom"
                v-bind:group_name="group.ugroup_name"
                v-bind:data-test="`git-permission-badge-${group.ugroup_name}`"
            />
        </td>

        <td>
            <git-permissions-badge
                v-for="group in repository_permission.writers"
                v-bind:key="group.ugroup_name"
                v-bind:is_project_admin="group.is_project_admin"
                v-bind:is_static="group.is_static"
                v-bind:is_custom="group.is_custom"
                v-bind:group_name="group.ugroup_name"
                v-bind:data-test="`git-permission-badge-${group.ugroup_name}`"
            />
        </td>
        <td>
            <git-permissions-badge
                v-for="group in repository_permission.rewinders"
                v-bind:key="group.ugroup_name"
                v-bind:is_project_admin="group.is_project_admin"
                v-bind:is_static="group.is_static"
                v-bind:is_custom="group.is_custom"
                v-bind:group_name="group.ugroup_name"
                v-bind:data-test="`git-permission-badge-${group.ugroup_name}`"
            />
        </td>
    </tr>
</template>
<script setup lang="ts">
import GitPermissionsBadge from "@tuleap/vue3-permissions-per-group-badge";
import type { RepositorySimplePermissions } from "./type";

defineProps<{ repository_permission: RepositorySimplePermissions }>();
</script>
