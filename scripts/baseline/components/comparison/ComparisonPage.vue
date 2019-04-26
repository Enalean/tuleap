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
            class="tlp-alert-danger"
        >
            <translate>Cannot fetch baseline artifacts</translate>
        </div>

        <comparison-page-skeleton v-else-if="is_loading"/>

        <template v-else>
            <comparison-header v-bind:comparison="comparison"/>

            <comparison-statistics/>

            <div class="tlp-framed-vertically">
                <section class="tlp-pane">
                    <div class="tlp-pane-container">
                        <section class="tlp-pane-section">
                            <comparison-content/>
                        </section>
                    </div>
                </section>
            </div>
        </template>
    </div>
</template>

<script>
import ComparisonStatistics from "./ComparisonStatistics.vue";
import ComparisonPageSkeleton from "./ComparisonPageSkeleton.vue";
import ComparisonHeader from "./ComparisonHeader.vue";
import ComparisonContent from "./content/ComparisonContent.vue";
import { sprintf } from "sprintf-js";
import { mapGetters } from "vuex";

export default {
    name: "ComparisonPage",
    components: {
        ComparisonPageSkeleton,
        ComparisonContent,
        ComparisonHeader,
        ComparisonStatistics
    },
    props: {
        comparison: { required: true, type: Object }
    },

    data() {
        return {
            is_loading: true,
            is_loading_failed: false
        };
    },

    computed: {
        ...mapGetters(["findBaselineById"]),
        base_baseline() {
            return this.findBaselineById(this.comparison.base_baseline_id);
        },
        compared_to_baseline() {
            return this.findBaselineById(this.comparison.compared_to_baseline_id);
        }
    },

    mounted() {
        this.loadComparison();
    },

    created() {
        const title = sprintf(
            this.$gettext("Baselines comparison #%u/#%u"),
            this.comparison.base_baseline_id,
            this.comparison.compared_to_baseline_id
        );
        this.$emit("title", title);
    },

    methods: {
        async loadComparison() {
            this.is_loading = true;
            this.is_loading_failed = false;

            try {
                await this.$store.dispatch("comparison/load", this.comparison);
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>
