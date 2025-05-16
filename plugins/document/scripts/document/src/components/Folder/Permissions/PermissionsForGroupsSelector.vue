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
            v-bind:label="`${$gettext('Reader')}`"
            v-bind:project_ugroups="project_ugroups"
            v-bind:selected_ugroups="value.can_read"
            v-bind:key="'permissions-selector-can_read'"
            v-bind:identifier="can_read"
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
            v-bind:label="`${$gettext('Writer')}`"
            v-bind:project_ugroups="project_ugroups"
            v-bind:selected_ugroups="value.can_write"
            v-bind:key="'permissions-selector-can_write'"
            v-bind:identifier="can_write"
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
            v-bind:label="`${$gettext('Manager')}`"
            v-bind:project_ugroups="project_ugroups"
            v-bind:selected_ugroups="value.can_manage"
            v-bind:key="'permission-selectors-can_manage'"
            v-bind:identifier="can_manage"
        />
    </div>
</template>

<script>
import PermissionsSelector from "./PermissionsSelector.vue";
import { CAN_MANAGE, CAN_READ, CAN_WRITE } from "../../../constants";

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
        can_read() {
            return CAN_READ;
        },
        can_write() {
            return CAN_WRITE;
        },
        can_manage() {
            return CAN_MANAGE;
        },
    },
};
</script>
