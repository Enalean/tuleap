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
    <table class="tlp-table permission-per-group-table">
        <thead>
            <tr class="permission-per-group-triple-column-table">
                <th v-translate>Tracker name</th>
                <th v-translate>Permissions</th>
                <th v-translate>Granted groups</th>
            </tr>
        </thead>

        <table-content v-bind="$props" />
    </table>
</template>
<script>
import TableContent from "./TrackerPermissionsTableContent.vue";

export default {
    name: "TrackerPermissionsTable",
    components: {
        TableContent,
    },
    props: {
        trackerPermissions: Array,
        selectedUgroupName: String,
    },
};
</script>
