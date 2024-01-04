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
        <package-permissions-table-package-rows
            v-for="packages in package_permissions"
            v-bind:package="packages"
            v-bind:key="packages.package_name"
        />

        <empty-state v-if="!has_permissions" v-bind:selected_ugroup_name="selected_ugroup_name" />
    </tbody>
</template>

<script setup lang="ts">
import EmptyState from "./FRSPackagePermissionsTablePackageEmptyState.vue";
import type { PackagePermission } from "./types";
import { computed } from "vue";
import PackagePermissionsTablePackageRows from "./PackagePermissionsTablePackageRows.vue";

const props = defineProps<{
    package_permissions: PackagePermission[];
    selected_ugroup_name: string;
}>();

const has_permissions = computed((): boolean => props.package_permissions.length > 0);
</script>
