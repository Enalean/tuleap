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
    <tbody>
        <template v-for="tracker in tracker_permissions" v-bind:key="tracker.tracker_name">
            <tr>
                <td>
                    <a v-bind:href="tracker.admin_quick_link">{{ tracker.tracker_name }}</a>
                </td>
                <td></td>
                <td></td>
            </tr>

            <tr
                v-for="permission in tracker.permissions"
                v-bind:key="tracker.tracker_name + permission.permission_name"
            >
                <td></td>
                <td>{{ permission.permission_name }}</td>

                <td>
                    <tracker-permissions-ugroup-badge
                        v-for="group in permission.granted_groups"
                        v-bind:key="group.ugroup_name"
                        v-bind:is_project_admin="group.is_project_admin"
                        v-bind:is_static="group.is_static"
                        v-bind:is_custom="group.is_custom"
                        v-bind:group_name="group.ugroup_name"
                    />
                </td>
            </tr>
        </template>

        <tr v-if="!has_tracker_permissions">
            <empty-state v-bind:selected_ugroup_name="selected_ugroup_name" />
        </tr>
    </tbody>
</template>
<script setup lang="ts">
import TrackerPermissionsUgroupBadge from "@tuleap/vue3-permissions-per-group-badge";
import EmptyState from "./TrackerPermissionTableEmptyState.vue";
import type { TrackerPermissions } from "./rest-querier.js";
import { computed } from "vue";

const props = defineProps<{
    tracker_permissions: TrackerPermissions;
    selected_ugroup_name: string;
}>();

const has_tracker_permissions = computed(() => props.tracker_permissions.length > 0);
</script>
