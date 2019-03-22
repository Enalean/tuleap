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
    <ol class="comparison-content-artifact-ol">
        <li
            v-for="comparison in artifact_comparisons"
            v-bind:key="comparison.reference.id"
            class="comparison-content-artifact-li"
        >
            <modified-artifact v-bind:reference="comparison.reference" v-bind:compared_to="comparison.compared_to"/>
        </li>
        <li
            v-for="artifact in added_artifacts"
            v-bind:key="artifact.id"
            class="comparison-content-artifact-li comparison-content-artifact-added"
        >
            <artifact-label v-bind:artifact="artifact" class="comparison-content-artifact-header"/>
        </li>
        <li
            v-for="artifact in removed_artifacts"
            v-bind:key="artifact.id"
            class="comparison-content-artifact-li comparison-content-artifact-removed"
        >
            <artifact-label v-bind:artifact="artifact" class="comparison-content-artifact-header"/>
        </li>
    </ol>
</template>

<script>
import ModifiedArtifact from "./ModifiedArtifact.vue";
import ArtifactLabel from "../../common/ArtifactLabel.vue";

export default {
    name: "ArtifactsComparison",

    components: { ModifiedArtifact, ArtifactLabel },

    props: {
        reference_artifacts: { require: true, type: Array },
        compared_artifacts: { require: true, type: Array }
    },

    computed: {
        artifact_comparisons() {
            return this.reference_artifacts
                .map(reference => {
                    const matching_comparisons = this.compared_artifacts.filter(
                        compared => reference.id === compared.id
                    );
                    if (matching_comparisons.length === 0) {
                        // Artifact was removed
                        return null;
                    }
                    return {
                        reference,
                        compared_to: matching_comparisons[0]
                    };
                })
                .filter(modification => modification !== null);
        },
        added_artifacts() {
            return this.compared_artifacts.filter(compared =>
                this.reference_artifacts.every(reference => compared.id !== reference.id)
            );
        },
        removed_artifacts() {
            return this.reference_artifacts.filter(reference =>
                this.compared_artifacts.every(compared => reference.id !== compared.id)
            );
        }
    }
};
</script>
