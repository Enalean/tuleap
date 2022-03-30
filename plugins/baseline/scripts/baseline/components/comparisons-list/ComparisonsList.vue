<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <table class="tlp-table comparisons-table">
        <thead>
            <tr>
                <th class="tlp-table-cell-numeric" v-translate>Id</th>
                <th class="comparisons-table-column" v-translate>Milestone</th>
                <th class="comparisons-table-column" v-translate>Reference</th>
                <th class="comparisons-table-column" v-translate>Compared to</th>
                <th></th>
            </tr>
        </thead>

        <tbody v-if="is_loading">
            <comparison-skeleton />
            <comparison-skeleton />
            <comparison-skeleton />
        </tbody>
        <tbody v-else-if="are_some_available">
            <comparison-item
                v-for="comparison in comparisons"
                v-bind:key="comparison.id"
                v-bind:comparison="comparison"
            />
        </tbody>

        <tbody v-else>
            <tr>
                <td
                    colspan="6"
                    class="tlp-table-cell-empty"
                    key="no-comparison"
                    data-test-type="empty-comparison"
                    v-translate
                >
                    No comparison available
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
import ComparisonSkeleton from "./ComparisonSkeleton.vue";
import ComparisonItem from "./ComparisonItem.vue";
import { mapState, mapGetters } from "vuex";

export default {
    name: "ComparisonsList",

    components: { ComparisonSkeleton, ComparisonItem },

    props: {
        project_id: { required: true, type: Number },
    },

    computed: {
        ...mapState("comparisons", ["comparisons", "is_loading"]),
        ...mapGetters("comparisons", ["are_some_available"]),
    },

    mounted() {
        this.$store.dispatch("comparisons/load", { project_id: this.project_id });
    },
};
</script>
