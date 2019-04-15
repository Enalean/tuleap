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
        <artifacts-list-comparison-skeleton v-if="is_loading"/>

        <div v-else-if="is_loading_failed">
            <div class="tlp-alert-danger">
                <translate>Cannot fetch baseline artifacts</translate>
            </div>
        </div>

        <span
            v-else-if="!are_some_artifacts_available"
            class="baseline-empty-information-message"
            v-translate
        >
            No artifact to compare
        </span>

        <artifacts-list-comparison
            v-else
            v-bind:current_depth="1"
            v-bind:reference_artifacts="reference_artifacts"
            v-bind:compared_artifacts="compared_artifacts"
        />
    </div>
</template>

<script>
import { getBaselineArtifacts } from "../../../api/rest-querier";
import { presentArtifacts } from "../../../presenters/baseline";
import ArtifactsListComparisonSkeleton from "./ArtifactsListComparisonSkeleton.vue";
import ArtifactsListComparison from "./ArtifactsListComparison.vue";

export default {
    name: "ComparisonContent",

    components: { ArtifactsListComparisonSkeleton, ArtifactsListComparison },

    props: {
        from_baseline_id: { require: true, type: Number },
        to_baseline_id: { require: true, type: Number }
    },

    data() {
        return {
            reference_artifacts: null,
            compared_artifacts: null,
            is_loading_failed: false,
            is_loading: true
        };
    },

    computed: {
        are_some_artifacts_available() {
            return (
                this.reference_artifacts !== null &&
                this.reference_artifacts.length > 0 &&
                this.compared_artifacts !== null &&
                this.compared_artifacts.length > 0
            );
        }
    },

    mounted() {
        this.fetchArtifacts();
    },

    methods: {
        async fetchArtifacts() {
            try {
                const reference_artifacts = this.getPresentedLinkedArtifacts(this.from_baseline_id);
                const compared_artifacts = this.getPresentedLinkedArtifacts(this.to_baseline_id);

                this.reference_artifacts = await reference_artifacts;
                this.compared_artifacts = await compared_artifacts;
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        },

        async getPresentedLinkedArtifacts(baseline_id) {
            const artifacts = await getBaselineArtifacts(baseline_id);
            return presentArtifacts(artifacts, baseline_id);
        }
    }
};
</script>
