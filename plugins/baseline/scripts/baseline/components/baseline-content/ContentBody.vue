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
        <div
            v-if="first_depth_artifacts.length === 0"
            class="baseline-empty-information-message"
            data-test-type="empty-artifact-message"
        >
            <translate>No artifacts</translate>
        </div>

        <div
            v-else-if="filtered_artifacts.length === 0"
            class="baseline-empty-information-message"
            data-test-type="all-artifacts-filtered-message"
        >
            <translate>All artifacts are hidden</translate>
        </div>

        <artifacts-list v-bind:artifacts="filtered_artifacts" />
    </div>
</template>

<script>
import ArtifactsList from "./ArtifactsList.vue";
import { mapState, mapGetters } from "vuex";

export default {
    name: "ContentBody",

    components: { ArtifactsList },

    computed: {
        ...mapState("current_baseline", ["first_depth_artifacts"]),
        ...mapGetters("current_baseline", ["filterArtifacts"]),
        filtered_artifacts() {
            return this.filterArtifacts(this.first_depth_artifacts);
        },
    },
};
</script>
