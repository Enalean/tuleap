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
    <div class="comparison-content">
        <artifacts-list-comparison
            v-if="are_some_artifacts_visible"
            v-bind:base_artifacts="filtered_first_depth_base_artifacts"
            v-bind:compared_to_artifacts="filtered_first_depth_compared_to_artifacts"
        />
        <div
            v-else-if="are_some_artifacts_available"
            class="baseline-empty-information-message"
            data-test-type="all-artifacts-filtered-message"
        >
            <translate>All artifacts are hidden</translate>
        </div>
        <span
            v-else
            class="baseline-empty-information-message"
            data-test-type="no-comparison-available-message"
            v-translate
        >
            No artifact to compare
        </span>
    </div>
</template>

<script>
import ArtifactsListComparison from "./ArtifactsListComparison.vue";
import { mapState, mapGetters } from "vuex";

export default {
    name: "ComparisonContent",

    components: { ArtifactsListComparison },

    computed: {
        ...mapState({
            first_depth_base_artifacts: (state) => state.comparison.base.first_depth_artifacts,
            first_depth_compared_to_artifacts: (state) =>
                state.comparison.compared_to.first_depth_artifacts,
        }),
        ...mapGetters("comparison", ["filterArtifacts"]),
        filtered_first_depth_base_artifacts() {
            return this.filterArtifacts(this.first_depth_base_artifacts);
        },
        filtered_first_depth_compared_to_artifacts() {
            return this.filterArtifacts(this.first_depth_compared_to_artifacts);
        },
        are_some_artifacts_visible() {
            return (
                this.filtered_first_depth_base_artifacts.length > 0 ||
                this.filtered_first_depth_compared_to_artifacts.length > 0
            );
        },
        are_some_artifacts_available() {
            return (
                this.first_depth_base_artifacts.length > 0 ||
                this.first_depth_compared_to_artifacts.length > 0
            );
        },
    },
};
</script>
