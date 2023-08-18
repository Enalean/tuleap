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
    <section class="tlp-pane-section">
        <div v-if="hasRestError" class="tlp-alert-danger">{{ rest_error }}</div>

        <div class="permission-per-group-load-button" v-if="!is_loaded">
            <button
                class="tlp-button-primary tlp-button-outline"
                v-on:click="loadAll()"
                v-translate
            >
                See all packages permissions
            </button>
        </div>

        <div
            v-if="is_loading"
            v-bind:aria-label="packages_are_loading"
            class="permission-per-group-loader"
        ></div>

        <package-permissions-table
            v-if="is_loaded"
            v-bind:package-permissions="packages_list"
            v-bind:selected-ugroup-name="selectedUgroupName"
        />
    </section>
</template>
<script lang="ts">
import { getPackagesPermissions } from "./api/rest-querier";
import PackagePermissionsTable from "./FRSPackagePermissionsTable.vue";
import Component from "vue-class-component";
import { Prop } from "vue-property-decorator";
import Vue from "vue";
import type { PackagePermission } from "./types";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

@Component({ components: { PackagePermissionsTable } })
export default class BaseFRSPackagePermissions extends Vue {
    @Prop()
    private readonly selectedUgroupId!: string;
    @Prop()
    private readonly selectedProjectId!: string;
    @Prop()
    readonly selectedUgroupName!: string;

    is_loaded = false;
    is_loading = false;
    rest_error: string | null = null;
    packages_list: PackagePermission[] = [];

    get hasRestError(): boolean {
        return this.rest_error !== null;
    }

    get packages_are_loading(): string {
        return this.$gettext("Packages are loading");
    }

    async loadAll(): Promise<void> {
        try {
            this.is_loading = true;

            this.packages_list = await getPackagesPermissions(
                this.selectedProjectId,
                this.selectedUgroupId
            );

            this.is_loaded = true;
        } catch (e) {
            if (!(e instanceof FetchWrapperError)) {
                throw e;
            }
            const { error } = await e.response.json();
            this.rest_error = error;
        } finally {
            this.is_loading = false;
        }
    }
}
</script>
