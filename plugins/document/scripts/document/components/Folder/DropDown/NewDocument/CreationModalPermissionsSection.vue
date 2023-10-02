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
    <div class="document-permissions-creation-modal-section">
        <hr class="tlp-modal-separator" />
        <div class="document-permissions-creation-modal-title">
            <div
                v-if="project_ugroups === null"
                class="document-permissions-creation-modal-title-spinner"
            >
                <i class="fa-solid fa-spin fa-circle-notch"></i>
            </div>
            <h2 class="tlp-modal-subtitle">
                {{ $gettext("Permissions") }}
            </h2>
        </div>
        <permissions-for-groups-selector
            v-if="project_ugroups !== null"
            v-bind:project_ugroups="project_ugroups"
            v-bind:value="value"
            data-test="document-creation-permissions-section-selector"
        />
    </div>
</template>
<script>
import PermissionsForGroupsSelector from "../../Permissions/PermissionsForGroupsSelector.vue";

export default {
    name: "CreationModalPermissionsSection",
    components: {
        PermissionsForGroupsSelector,
    },
    props: {
        project_ugroups: {
            validator: (prop) => Array.isArray(prop) || prop === null,
            required: true,
        },
        value: {
            type: Object,
            required: true,
        },
    },
};
</script>
