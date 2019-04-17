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
    <div class="baseline-content">
        <div
            v-if="first_level_artifacts.length === 0"
            class="baseline-empty-information-message"
            data-test-type="information-message"
        >
            <translate>No artifacts</translate>
        </div>

        <artifacts-list
            v-bind:artifacts="first_level_artifacts"
        />
    </div>
</template>

<script>
import ArtifactsList from "./ArtifactsList.vue";

export default {
    name: "ContentBody",

    components: { ArtifactsList },

    props: {
        first_level_artifacts: { required: true, type: Array }
    },

    mounted() {
        this.reset();
    },

    methods: {
        reset() {
            this.$store.commit("semantics/reset");
        }
    }
};
</script>
