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
    <td
        v-if="hasASelectedUserGroup"
        key="selected-ugroup"
        colspan="3"
        class="tlp-table-cell-empty"
        v-translate="{ user_group: selectedUgroupName }"
    >
        %{ user_group } has no permission for trackers
    </td>

    <td v-else key="no-selected-ugroup" colspan="3" class="tlp-table-cell-empty" v-translate>
        Project has no tracker
    </td>
</template>
<script>
export default {
    name: "TrackerPermissionTableEmptyState",
    props: {
        selectedUgroupName: String,
    },
    computed: {
        hasASelectedUserGroup() {
            return this.selectedUgroupName !== "";
        },
    },
};
</script>
