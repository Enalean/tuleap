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
    <div class="tlp-framed-vertically">
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <section class="tlp-pane-section comparison-content">
                    <artifacts-list-comparison-skeleton v-if="is_comparison_loading"/>

                    <div v-else-if="is_comparison_loading_failed">
                        <div class="tlp-alert-danger">
                            <translate>Cannot fetch baseline artifacts</translate>
                        </div>
                    </div>

                    <artifacts-list-comparison
                        v-else-if="are_some_first_level_artifacts_available"
                        v-bind:reference_artifacts="base_baseline.first_level_artifacts"
                        v-bind:compared_artifacts="compared_to_baseline.first_level_artifacts"
                    />
                    <span
                        v-else
                        class="baseline-empty-information-message"
                        v-translate
                    >
                        No artifact to compare
                    </span>
                </section>
            </div>
        </section>
    </div>
</template>

<script>
import ArtifactsListComparisonSkeleton from "./ArtifactsListComparisonSkeleton.vue";
import ArtifactsListComparison from "./ArtifactsListComparison.vue";
import { mapState } from "vuex";

export default {
    name: "ComparisonContent",

    components: { ArtifactsListComparisonSkeleton, ArtifactsListComparison },

    props: {
        from_baseline_id: { require: true, type: Number },
        to_baseline_id: { require: true, type: Number }
    },

    computed: {
        ...mapState("comparison", [
            "base_baseline",
            "compared_to_baseline",
            "is_comparison_loading_failed",
            "is_comparison_loading"
        ]),

        is_base_baseline_available() {
            return this.base_baseline !== null && this.base_baseline !== undefined;
        },

        is_compared_to_baseline_available() {
            return this.base_baseline !== null && this.base_baseline !== undefined;
        },

        are_some_first_level_artifacts_available() {
            return (
                !this.is_comparison_loading &&
                this.is_base_baseline_available &&
                this.is_compared_to_baseline_available &&
                this.base_baseline.first_level_artifacts.length > 0 &&
                this.compared_to_baseline.first_level_artifacts.length > 0
            );
        }
    },

    mounted() {
        this.$store.dispatch("comparison/load", {
            base_baseline_id: this.from_baseline_id,
            compared_to_baseline_id: this.to_baseline_id
        });
    }
};
</script>
