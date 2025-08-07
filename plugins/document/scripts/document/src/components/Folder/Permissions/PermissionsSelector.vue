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
    <div class="tlp-form-element">
        <label class="tlp-label" v-bind:for="selector_id">{{ label }}</label>
        <select
            v-bind:id="selector_id"
            v-bind:data-test="selector_id"
            class="tlp-select"
            multiple
            v-model="selected_ugroup_ids"
            v-on:change="updateSelectedUGroups"
        >
            <option
                v-for="ugroup in project_ugroups"
                v-bind:value="ugroup.id"
                v-bind:key="`permissions-${label}-${ugroup.id}`"
                v-bind:title="ugroup.label"
            >
                {{ ugroup.label }}
            </option>
        </select>
        <slot name="permission-information"></slot>
    </div>
</template>
<script setup lang="ts">
import emitter from "../../../helpers/emitter";
import type { CAN_MANAGE, CAN_READ, CAN_WRITE } from "../../../constants";
import type { Permission, UserGroup } from "../../../type";
import { ref, watch } from "vue";

const props = defineProps<{
    label: string;
    project_ugroups: Array<UserGroup>;
    selected_ugroups: Array<Permission>;
    identifier: CAN_READ | CAN_WRITE | CAN_MANAGE;
}>();

function getSelectedUGroupsIDs(selected_ugroups: Array<Permission>): string[] {
    if (!selected_ugroups) {
        return [];
    }
    return selected_ugroups.map((ugroup) => ugroup.id);
}

const selected_ugroup_ids = ref<string[]>(getSelectedUGroupsIDs(props.selected_ugroups));
const selector_id = "document-permission-" + props.label;

watch(
    () => props.selected_ugroups,
    (value) => {
        selected_ugroup_ids.value = getSelectedUGroupsIDs(value);
    },
);

function updateSelectedUGroups() {
    emitter.emit("update-permissions", {
        label: props.identifier,
        value: selected_ugroup_ids.value.map((ugroup_id) => ({ id: ugroup_id })),
    });
}
</script>
