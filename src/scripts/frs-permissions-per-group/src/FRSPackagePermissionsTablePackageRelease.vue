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
        <td></td>
        <td>
            <a v-bind:href="release.release_url">{{ release.release_name }}</a>
        </td>
        <td>
            <ugroup-badge
                v-for="ugroup in release.release_permissions"
                v-bind:key="ugroup.ugroup_name"
                v-bind:is-project-admin="ugroup.is_project_admin"
                v-bind:is-static="ugroup.is_static"
                v-bind:is-custom="ugroup.is_custom"
                v-bind:group-name="ugroup.ugroup_name"
            />
        </td>
    </tr>
</template>
<script lang="ts">
import UgroupBadge from "@tuleap/vue-permissions-per-group-badge";
import Component from "vue-class-component";
import Vue from "vue";
import type { PackagePermissionPerGroupReleaseRepresentation } from "./types";
import { Prop } from "vue-property-decorator";

@Component({ components: { UgroupBadge } })
export default class FRSPackagePermissionsTablePackageRelease extends Vue {
    @Prop()
    readonly release!: PackagePermissionPerGroupReleaseRepresentation;
}
</script>
