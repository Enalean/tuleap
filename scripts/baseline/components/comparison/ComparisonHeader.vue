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
    <div>
        <transient-comparison-label
            v-if="!comparison.hasOwnProperty('id')"
            v-bind:base_baseline_id="comparison.base_baseline_id"
            v-bind:compared_to_baseline_id="comparison.compared_to_baseline_id"
        />

        <h2>
            <template v-if="comparison.name">{{ comparison.name }} -</template>
            {{ base_baseline.name }}
            <i class="fa fa-tlp-baseline-comparison baseline-comparison-separator"></i>
            {{ compared_to_baseline.name }}
        </h2>
    </div>
</template>

<script>
import TransientComparisonLabel from "./TransientComparisonLabel.vue";
import { mapGetters } from "vuex";

export default {
    name: "ComparisonHeader",

    components: {
        TransientComparisonLabel,
    },

    props: {
        comparison: { required: true, type: Object },
    },

    computed: {
        ...mapGetters(["findBaselineById"]),
        base_baseline() {
            return this.findBaselineById(this.comparison.base_baseline_id);
        },
        compared_to_baseline() {
            return this.findBaselineById(this.comparison.compared_to_baseline_id);
        },
    },
};
</script>
