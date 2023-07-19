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
    <table class="tlp-table permission-per-group-table">
        <thead>
            <tr class="permission-per-group-triple-column-table">
                <th v-translate>Package</th>
                <th v-translate>Release</th>
                <th v-translate>Readers</th>
            </tr>
        </thead>

        <package-permissions v-bind="$props" />
    </table>
</template>
<script lang="ts">
import PackagePermissions from "./FRSPackagePermissionsTablePackage.vue";
import Component from "vue-class-component";
import Vue from "vue";
import type { PackagePermission } from "./types";
import { Prop } from "vue-property-decorator";

@Component({ components: { PackagePermissions } })
export default class FRSPackagePermissionsTable extends Vue {
    @Prop()
    private readonly packagePermissions!: PackagePermission;
    @Prop()
    private readonly selectedUgroupName!: string;
}
</script>
