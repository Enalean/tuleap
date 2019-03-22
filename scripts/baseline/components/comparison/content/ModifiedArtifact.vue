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
    <span>
        <artifact-label v-bind:artifact="reference" class="comparison-content-artifact-header"/>
        <div>
            <field-comparison
                v-if="reference.description !== compared_to.description"
                semantic="description"
                v-bind:tracker_id="compared_to.tracker_id"
                v-bind:reference="reference.description"
                v-bind:compare_to="compared_to.description"
            />
            <field-comparison
                v-if="reference.status !== compared_to.status"
                semantic="status"
                v-bind:tracker_id="compared_to.tracker_id"
                v-bind:reference="reference.status"
                v-bind:compare_to="compared_to.status"
            />
        </div>

        <artifacts-comparison-skeleton v-if="is_loading"/>
        <span
            v-else-if="is_loading_failed"
            class="tlp-tooltip tlp-tooltip-right baseline-content-artifact-error-message"
            v-bind:data-tlp-tooltip="error_message_tooltip"
        >
            <i
                class="fa fa-exclamation-circle baseline-tooltip-icon"
            ></i>
        </span>
        <artifacts-comparison
            v-else
            v-bind:reference_artifacts="reference_linked_artifacts"
            v-bind:compared_artifacts="compared_linked_artifacts"
        />
    </span>
</template>

<script>
import { getBaselineArtifactsByIds } from "../../../api/rest-querier";
import { presentArtifacts } from "../../../presenters/baseline";
import ArtifactsComparisonSkeleton from "./ArtifactsComparisonSkeleton.vue";
import ArtifactsComparison from "./ArtifactsComparison.vue";
import FieldComparison from "./FieldComparison.vue";
import ArtifactLabel from "../../common/ArtifactLabel.vue";

export default {
    name: "ModifiedArtifact",

    components: {
        ArtifactLabel,
        FieldComparison,
        ArtifactsComparisonSkeleton,
        ArtifactsComparison
    },

    props: {
        reference: { require: true, type: Object },
        compared_to: { required: true, type: Object }
    },

    data() {
        return {
            reference_linked_artifacts: null,
            compared_linked_artifacts: null,
            is_loading: true,
            is_loading_failed: false
        };
    },

    beforeCreate() {
        this.$options.components.ArtifactsComparison = ArtifactsComparison;
    },

    mounted() {
        this.fetchLinkedArtifacts();
    },

    computed: {
        error_message_tooltip() {
            return this.$gettext("Cannot fetch linked artifacts");
        }
    },

    methods: {
        async fetchLinkedArtifacts() {
            try {
                const reference_linked_artifact = this.getPresentedLinkedArtifacts(this.reference);
                const compared_linked_artifacts = this.getPresentedLinkedArtifacts(
                    this.compared_to
                );

                this.reference_linked_artifacts = await reference_linked_artifact;
                this.compared_linked_artifacts = await compared_linked_artifacts;
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        },
        async getPresentedLinkedArtifacts(artifact) {
            const linked_artifacts = await getBaselineArtifactsByIds(
                artifact.baseline_id,
                artifact.linked_artifact_ids
            );
            return presentArtifacts(linked_artifacts, artifact.baseline_id);
        }
    }
};
</script>
