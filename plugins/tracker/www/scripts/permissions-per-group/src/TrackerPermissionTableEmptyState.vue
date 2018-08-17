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
    <td v-if="hasASelectedUserGroup" colspan="3" class="tlp-table-cell-empty">
        {{ no_permission_empty_state }}
    </td>

    <td v-else colspan="3" class="tlp-table-cell-empty">
        {{ no_tracker_empty_state }}
    </td>
</template>)
(<script>
import { sprintf } from "sprintf-js";
import { gettext_provider } from "./gettext-provider.js";

export default {
    name: "TrackerPermissionsTableEmptyState",
    props: {
        selectedUgroupName: String
    },
    computed: {
        hasASelectedUserGroup() {
            return this.selectedUgroupName !== "";
        },
        no_permission_empty_state() {
            return sprintf(
                gettext_provider.gettext("%s has no permission for trackers"),
                this.selectedUgroupName
            );
        },
        no_tracker_empty_state: () => gettext_provider.gettext("Project has no tracker")
    }
};
</script>)
