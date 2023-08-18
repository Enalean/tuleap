<!--
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
        <td
            v-if="hasASelectedUserGroup"
            key="selected-ugroup"
            colspan="3"
            class="tlp-table-cell-empty"
            v-translate="{ user_group: selectedUgroupName }"
        >
            %{ user_group } has no permissions for any package or release
        </td>

        <td v-else key="no-selected-ugroup" colspan="3" class="tlp-table-cell-empty" v-translate>
            No packages found for project
        </td>
    </tr>
</template>
<script lang="ts">
import Component from "vue-class-component";
import Vue from "vue";
import { Prop } from "vue-property-decorator";

@Component
export default class FRSPackagePermissionsTablePackageEmptyState extends Vue {
    @Prop()
    readonly selectedUgroupName!: string;

    get hasASelectedUserGroup(): boolean {
        return this.selectedUgroupName !== "";
    }
}
</script>
