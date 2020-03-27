<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
            v-bind:icons="['fa-folder', 'fa-folder', 'fa-file-o', 'fa-file-o', 'fa-file-o']"
            v-bind:cell_classes="['', 'document-tree-cell-owner', 'document-tree-cell-updatedate']"
        >
            <th class="document-tree-head-name" v-translate>Name</th>
            <th class="document-tree-head-owner document-tree-head-skeleton" v-translate>Owner</th>
            <th class="document-tree-head-updatedate document-tree-head-skeleton" v-translate>
                Last update date
            </th>
        </skeleton-table>
    </div>
</template>

<script>
import SkeletonTable from "../../../../../../src/www/scripts/vue-components/skeletons/SkeletonTable.vue";

export default {
    name: "FolderLoadingScreen",
    components: { SkeletonTable },
};
</script>
