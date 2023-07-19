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
    <tbody>
        <template v-for="packages in packagePermissions">
            <tr v-bind:key="packages.package_name">
                <td>
                    <a v-bind:href="packages.package_url">{{ packages.package_name }}</a>
                </td>
                <td></td>
                <td>
                    <ugroup-badge
                        v-for="ugroup in packages.permissions"
                        v-bind:key="ugroup.ugroup_name"
                        v-bind:is-project-admin="ugroup.is_project_admin"
                        v-bind:is-static="ugroup.is_static"
                        v-bind:is-custom="ugroup.is_custom"
                        v-bind:group-name="ugroup.ugroup_name"
                    />
                </td>
            </tr>

            <release-permissions
                v-for="release in packages.releases"
                v-bind:key="release.release_name"
                v-bind:release="release"
            />
        </template>

        <empty-state v-if="!has_permissions" v-bind:selected-ugroup-name="selectedUgroupName" />
    </tbody>
</template>
<script lang="ts">
import UgroupBadge from "@tuleap/vue-permissions-per-group-badge";
import EmptyState from "./FRSPackagePermissionsTablePackageEmptyState.vue";
import ReleasePermissions from "./FRSPackagePermissionsTablePackageRelease.vue";
import Component from "vue-class-component";
import Vue from "vue";
import { Prop } from "vue-property-decorator";
import type { PackagePermission } from "./types";

@Component({ components: { EmptyState, UgroupBadge, ReleasePermissions } })
export default class FRSPackagePermissionsTablePackage extends Vue {
    @Prop()
    private readonly packagePermissions!: PackagePermission[];
    @Prop()
    private readonly selectedUgroupName!: string;

    get has_permissions(): boolean {
        return this.packagePermissions.length > 0;
    }
}
</script>
