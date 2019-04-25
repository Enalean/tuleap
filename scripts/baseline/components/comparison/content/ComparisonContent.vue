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
    <div class="comparison-content">
        <artifacts-list-comparison
            v-if="are_some_artifacts_available"
            v-bind:base_artifacts="first_level_base_artifacts"
            v-bind:compared_to_artifacts="first_level_compared_to_artifacts"
        />
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
import { mapState } from "vuex";

export default {
    name: "ComparisonContent",

    components: { ArtifactsListComparison },

    computed: {
        ...mapState("comparison", [
            "first_level_base_artifacts",
            "first_level_compared_to_artifacts"
        ]),
        are_some_artifacts_available() {
            return (
                this.first_level_base_artifacts.length > 0 ||
                this.first_level_compared_to_artifacts.length > 0
            );
        }
    }
};
</script>
