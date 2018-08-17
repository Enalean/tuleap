/**
* Copyright Enalean (c) 2018. All rights reserved.
*
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
*
* This file is a part of Tuleap.
*
* Tuleap is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Tuleap is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
*/

(<template>
    <tbody>
        <template v-for="tracker in trackerPermissions">
            <tr>
                <td>
                    <a v-bind:href="tracker.admin_quick_link">{{ tracker.tracker_name }}</a>
                </td>
                <td></td>
                <td></td>
            </tr>

            <tr v-for="permission in tracker.permissions" v-bind:key="tracker.tracker_name + permission.permission_name">
                <td></td>
                <td>{{ permission.permission_name }}</td>

                <td>
                    <tracker-permissions-ugroup-badge v-for="group in permission.granted_groups"
                      v-bind:key="group.ugroup_name"
                      v-bind:is-project-admin="group.is_project_admin"
                      v-bind:is-static="group.is_static"
                      v-bind:is-custom="group.is_custom"
                      v-bind:group-name="group.ugroup_name"
                    >
                    </tracker-permissions-ugroup-badge>
                </td>
            </tr>
        </template>

        <tr v-if="! hasTrackerPermissions">
            <empty-state v-bind:selected-ugroup-name="selectedUgroupName"></empty-state>
        </tr>
    </tbody>
</template>)
(<script>
import { gettext_provider } from "./gettext-provider.js";
import TrackerPermissionsUgroupBadge from "permission-badge/PermissionsPerGroupBadge.vue";
import EmptyState from "./TrackerPermissionTableEmptyState.vue";

export default {
    name: "TrackerPermissionsTableContent",
    components: {
        TrackerPermissionsUgroupBadge,
        EmptyState
    },
    props: {
        trackerPermissions: Array,
        selectedUgroupName: String
    },
    computed: {
        hasTrackerPermissions() {
            return this.trackerPermissions.length > 0;
        }
    }
};
</script>)
