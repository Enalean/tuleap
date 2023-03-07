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
    <div class="tlp-card">
        <skeleton-table
            v-bind:nb_rows="5"
            v-bind:nb_cols="3"
            v-bind:icons="[
                'fa-solid fa-folder',
                'fa-solid fa-folder',
                'fa-regular fa-file',
                'fa-regular fa-file',
                'fa-regular fa-file',
            ]"
            v-bind:cell_classes="['', 'document-tree-cell-owner', 'document-tree-cell-updatedate']"
        >
            <th class="document-tree-head-name">{{ $gettext("Name") }}</th>
            <th class="document-tree-head-owner document-tree-head-skeleton">
                {{ $gettext("Owner") }}
            </th>
            <th class="document-tree-head-updatedate document-tree-head-skeleton">
                {{ $gettext("Last update date") }}
            </th>
        </skeleton-table>
    </div>
</template>

<script setup lang="ts">
import SkeletonTable from "./SkeletonTable.vue";
</script>
