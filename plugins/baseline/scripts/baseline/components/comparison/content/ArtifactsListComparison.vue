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
    <ol class="comparison-content-artifact-ol">
        <li
            v-for="{ base, compared_to } in comparison.identical_or_modified"
            v-bind:key="base.id"
            class="comparison-content-artifact-li"
        >
            <artifact-comparison v-bind:base="base" v-bind:compared_to="compared_to" />
        </li>
        <li
            v-for="artifact in comparison.added"
            v-bind:key="artifact.id"
            class="comparison-content-artifact-li comparison-content-artifact-added"
        >
            <artifact-label v-bind:artifact="artifact" class="comparison-content-artifact-header" />
        </li>
        <li
            v-for="artifact in comparison.removed"
            v-bind:key="artifact.id"
            class="comparison-content-artifact-li comparison-content-artifact-removed"
        >
            <artifact-label v-bind:artifact="artifact" class="comparison-content-artifact-header" />
        </li>
    </ol>
</template>

<script>
import ArtifactComparison from "./ArtifactComparison.vue";
import ArtifactLabel from "../../common/ArtifactLabel.vue";
import { compareArtifacts } from "../../../support/comparison";

export default {
    name: "ArtifactsListComparison",

    components: { ArtifactComparison, ArtifactLabel },

    props: {
        base_artifacts: { require: true, type: Array },
        compared_to_artifacts: { require: true, type: Array },
    },

    computed: {
        comparison() {
            return compareArtifacts(this.base_artifacts, this.compared_to_artifacts);
        },
    },
};
</script>
