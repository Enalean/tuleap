<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <td
        v-if="has_a_selected_ugroup_name"
        key="selected-ugroup"
        colspan="3"
        class="tlp-table-cell-empty"
    >
        {{ message }}
    </td>

    <td v-else key="no-selected-ugroup" colspan="3" class="tlp-table-cell-empty">
        {{ $gettext("Project has no tracker") }}
    </td>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    selected_ugroup_name: string;
}>();

const has_a_selected_ugroup_name = computed(() => props.selected_ugroup_name !== "");

const { $gettext, interpolate } = useGettext();

const message = interpolate($gettext("%{ user_group } has no permission for trackers"), {
    user_group: props.selected_ugroup_name,
});
</script>
