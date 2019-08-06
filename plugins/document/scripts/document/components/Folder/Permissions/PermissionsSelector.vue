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
    <div class="tlp-form-element tlp-form-element-disabled">
        <label class="tlp-label" v-bind:for="selector_id">{{ label }}</label>
        <select v-bind:id="selector_id" class="tlp-select" multiple disabled>
            <option
                v-for="ugroup in project_ugroups"
                v-bind:value="ugroup.id"
                v-bind:selected="selected_ugroups.some(u => u.id === ugroup.id)"
                v-bind:key="`permissions-${label}-${ugroup.id}`"
            >
                {{ ugroup.label }}
            </option>
        </select>
    </div>
</template>
<script>
export default {
    name: "PermissionsSelector",
    props: {
        label: String,
        project_ugroups: Array,
        selected_ugroups: Array
    },
    computed: {
        selector_id() {
            return "document-permission-" + this.label;
        }
    }
};
</script>
