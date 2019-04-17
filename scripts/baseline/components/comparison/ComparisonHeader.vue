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
  -
  -->

<template>
    <div>
        <transient-comparison-label
            v-if="comparison === null"
            v-bind:from_baseline_id="from_baseline.id"
            v-bind:to_baseline_id="to_baseline.id"
        />
        <comparison-label v-else v-bind:comparison="comparison"/>
        <h2>
            {{ from_baseline.name }}
            <i class="fa fa-tlp-baseline-comparison baseline-comparison-separator"></i>
            {{ to_baseline.name }}
        </h2>
    </div>
</template>

<script>
import TransientComparisonLabel from "./TransientComparisonLabel.vue";
import ComparisonLabel from "./ComparisonLabel.vue";

export default {
    name: "ComparisonHeader",

    components: {
        TransientComparisonLabel,
        ComparisonLabel
    },

    props: {
        comparison: { required: false, type: Object, default: null },
        from_baseline: { required: true, type: Object },
        to_baseline: { required: true, type: Object }
    }
};
</script>
