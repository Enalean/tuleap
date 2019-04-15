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
        <div
            v-if="is_loading_failed"
            class="tlp-alert-danger tlp-framed-vertically"
            v-translate
        >
            Cannot fetch baselines
        </div>
        <comparison-header-skeleton v-else-if="is_loading"/>
        <comparison-header
            v-else
            v-bind:comparison="comparison"
            v-bind:from_baseline="from_baseline"
            v-bind:to_baseline="to_baseline"
        />
    </div>
</template>

<script>
import { getBaseline } from "../../api/rest-querier";
import ComparisonHeaderSkeleton from "./ComparisonHeaderSkeleton.vue";
import ComparisonHeader from "./ComparisonHeader.vue";

export default {
    name: "ComparisonHeaderAsync",

    components: {
        ComparisonHeaderSkeleton,
        ComparisonHeader
    },

    props: {
        comparison: { required: false, type: Object, default: null },
        from_baseline_id: { required: true, type: Number },
        to_baseline_id: { required: true, type: Number }
    },

    data() {
        return {
            from_baseline: null,
            to_baseline: null,
            is_loading: true,
            is_loading_failed: false
        };
    },

    mounted() {
        this.fetchBaselines();
    },

    methods: {
        async fetchBaselines() {
            this.is_loading = true;
            this.is_loading_failed = false;

            try {
                const from_baseline = getBaseline(this.from_baseline_id);
                const to_baseline = getBaseline(this.to_baseline_id);
                this.from_baseline = await from_baseline;
                this.to_baseline = await to_baseline;
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>
