<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
        <template v-for="tracker in trackerPermissions">
            <tr v-bind:key="tracker.tracker_name">
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
                        v-bind:is-project-admin="group.is_project_admin"
                        v-bind:is-static="group.is_static"
                        v-bind:is-custom="group.is_custom"
                        v-bind:group-name="group.ugroup_name"
                    />
                </td>
            </tr>
        </template>

        <tr v-if="!hasTrackerPermissions">
            <empty-state v-bind:selected-ugroup-name="selectedUgroupName" />
        </tr>
    </tbody>
</template>
<script>
import TrackerPermissionsUgroupBadge from "../../../../../src/www/scripts/project/admin/permissions-per-group/PermissionsPerGroupBadge.vue";
import EmptyState from "./TrackerPermissionTableEmptyState.vue";

export default {
    name: "TrackerPermissionsTableContent",
    components: {
        TrackerPermissionsUgroupBadge,
        EmptyState,
    },
    props: {
        trackerPermissions: Array,
        selectedUgroupName: String,
    },
    computed: {
        hasTrackerPermissions() {
            return this.trackerPermissions.length > 0;
        },
    },
};
</script>
