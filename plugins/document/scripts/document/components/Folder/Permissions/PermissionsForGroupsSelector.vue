<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
  -
  -->
<template>
    <div class="document-permissions-ugroups">
        <permissions-selector
            v-bind:label="label_reader"
            v-bind:project_ugroups="project_ugroups"
            v-model="permissions_for_groups.can_read"
            v-bind:key="'permissions-selector-can_read'"
        >
            <template #permission-information>
                <p class="tlp-text-info">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    {{
                        $gettext(
                            "Writers and Managers are also Readers. Redundant permissions are automatically de-duplicated.",
                        )
                    }}
                </p>
            </template>
        </permissions-selector>
        <permissions-selector
            v-bind:label="label_writer"
            v-bind:project_ugroups="project_ugroups"
            v-model="permissions_for_groups.can_write"
            v-bind:key="'permissions-selector-can_write'"
        >
            <template #permission-information>
                <p class="tlp-text-info">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    {{
                        $gettext(
                            "Managers are also Writers. Redundant permissions are automatically de-duplicated.",
                        )
                    }}
                </p>
            </template>
        </permissions-selector>
        <permissions-selector
            v-bind:label="label_manager"
            v-bind:project_ugroups="project_ugroups"
            v-model="permissions_for_groups.can_manage"
            v-bind:key="'permission-selectors-can_manage'"
        />
    </div>
</template>

<script>
import PermissionsSelector from "./PermissionsSelector.vue";

export default {
    name: "PermissionsForGroupsSelector",
    components: {
        PermissionsSelector,
    },
    props: {
        project_ugroups: {
            type: Array,
            required: true,
        },
        value: {
            type: Object,
            required: true,
        },
    },
    computed: {
        permissions_for_groups: {
            get() {
                return this.value;
            },
            set(value) {
                this.$emit("input", value);
            },
        },
        label_reader() {
            return this.$gettext("Reader");
        },
        label_writer() {
            return this.$gettext("Writer");
        },
        label_manager() {
            return this.$gettext("Manager");
        },
    },
};
</script>
