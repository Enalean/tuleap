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
    <table class="tlp-table permission-per-group-table">
        <thead>
            <tr class="permission-per-group-triple-column-table">
                <th>{{ $gettext("Tracker name") }}</th>
                <th>{{ $gettext("Permissions") }}</th>
                <th>{{ $gettext("Granted groups") }}</th>
            </tr>
        </thead>

        <table-content v-bind="props" />
    </table>
</template>
<script setup lang="ts">
import TableContent from "./TrackerPermissionsTableContent.vue";
import type { TrackerPermissions } from "./rest-querier";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    tracker_permissions: TrackerPermissions;
    selected_ugroup_name: string;
}>();

const { $gettext } = useGettext();
</script>
