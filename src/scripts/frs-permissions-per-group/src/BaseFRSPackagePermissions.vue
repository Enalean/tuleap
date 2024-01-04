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
        <div v-if="has_rest_error" class="tlp-alert-danger">
            {{ rest_error }}
        </div>

        <div class="permission-per-group-load-button" v-if="!is_loaded">
            <button class="tlp-button-primary tlp-button-outline" v-on:click="loadAll()">
                {{ $gettext("See all packages permissions") }}
            </button>
        </div>

        <div
            v-if="is_loading"
            v-bind:aria-label="packages_are_loading"
            class="permission-per-group-loader"
        ></div>

        <package-permissions-table
            v-if="is_loaded"
            v-bind:package_permissions="packages_list"
            v-bind:selected_ugroup_name="selected_ugroup_name"
        />
    </section>
</template>

<script setup lang="ts">
import { getPackagesPermissions } from "./api/rest-querier";
import PackagePermissionsTable from "./FRSPackagePermissionsTable.vue";
import type { PackagePermission } from "./types";
import { computed, ref } from "vue";
import type { Fault } from "@tuleap/fault";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    selected_project_id: number;
    selected_ugroup_id: string;
    selected_ugroup_name: string;
}>();

const rest_error = ref<string | null>(null);
const is_loaded = ref(false);
const is_loading = ref(false);
const has_rest_error = computed(() => rest_error.value !== null);
const packages_list = ref<PackagePermission[]>([]);

const { $gettext } = useGettext();

const packages_are_loading = ref($gettext("Packages are loading"));

function loadAll(): void {
    is_loading.value = true;
    getPackagesPermissions(props.selected_project_id, props.selected_ugroup_id).match(
        (permissions: PackagePermission[]) => {
            is_loaded.value = true;
            is_loading.value = false;
            packages_list.value = permissions;
        },
        (fault: Fault) => {
            is_loading.value = false;
            rest_error.value = String(fault);
        },
    );
}
</script>
