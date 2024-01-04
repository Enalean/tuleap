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
        <td>
            <a v-bind:href="package.package_url">{{ package.package_name }}</a>
        </td>
        <td></td>
        <td>
            <ugroup-badge
                v-for="ugroup in package.permissions"
                v-bind:key="ugroup.ugroup_name"
                v-bind:is_project_admin="ugroup.is_project_admin"
                v-bind:is_static="ugroup.is_static"
                v-bind:is_custom="ugroup.is_custom"
                v-bind:group_name="ugroup.ugroup_name"
            />
        </td>
    </tr>

    <release-permissions
        v-for="release in package.releases"
        v-bind:key="release.release_name"
        v-bind:release="release"
    />
</template>

<script setup lang="ts">
import UgroupBadge from "@tuleap/vue3-permissions-per-group-badge";
import ReleasePermissions from "./FRSPackagePermissionsTablePackageRelease.vue";
import type { PackagePermission } from "./types";

defineProps<{
    package: PackagePermission;
}>();
</script>
