<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <table class="tlp-table">
        <thead>
            <tr>
                <th class="tlp-table-cell-numeric" v-translate>
                    Id
                </th>
                <th v-translate class="baselines-table-column-name">
                    Name
                </th>
                <th v-translate class="baselines-table-column-milestone">
                    Milestone
                </th>
                <th v-translate class="baselines-table-column-snapshot-date">
                    Snapshot Date
                </th>
                <th v-translate class="baselines-table-column-author">
                    Author
                </th>
                <th>
                </th>
            </tr>
        </thead>

        <baselines-table-body-skeleton v-if="is_loading"/>

        <baselines-table-body-cells v-else-if="are_baselines_available" v-bind:baselines="baselines"/>

        <tbody v-else>
            <tr>
                <td colspan="6" class="tlp-table-cell-empty" key="no-baseline" data-test-type="empty-baseline" v-translate>
                    No baseline available
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
import BaselinesTableBodySkeleton from "./BaselinesTableBodySkeleton.vue";
import BaselinesTableBodyCells from "./BaselinesTableBodyCells.vue";

export default {
    name: "BaselinesTable",

    components: { BaselinesTableBodySkeleton, BaselinesTableBodyCells },

    props: {
        baselines: { required: false, type: Array },
        is_loading: { required: true, type: Boolean }
    },

    computed: {
        are_baselines_available() {
            return this.baselines !== null && this.baselines.length > 0;
        }
    }
};
</script>
