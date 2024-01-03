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
    <collapsable-content class="comparison-content-artifact">
        <artifact-label
            slot="header"
            v-bind:artifact="compared_to"
            class="comparison-content-artifact-header"
        />
        <div class="comparison-content-artifact-body">
            <field-comparison
                v-if="base.description !== compared_to.description"
                semantic="description"
                v-bind:tracker_id="compared_to.tracker_id"
                v-bind:base="base.description"
                v-bind:compared_to="compared_to.description"
            />
            <field-comparison
                v-if="base.status !== compared_to.status"
                semantic="status"
                v-bind:tracker_id="compared_to.tracker_id"
                v-bind:base="base.status"
                v-bind:compared_to="compared_to.status"
            />
        </div>

        <depth-limit-reached-message v-if="is_depth_limit_reached" />

        <artifacts-list-comparison
            v-else-if="are_linked_artifacts_available"
            v-bind:base_artifacts="filtered_base_linked_artifacts"
            v-bind:compared_to_artifacts="filtered_compared_to_linked_artifacts"
        />
    </collapsable-content>
</template>

<script>
import CollapsableContent from "../../common/CollapsableContent.vue";
import FieldComparison from "./FieldComparison.vue";
import ArtifactLabel from "../../common/ArtifactLabel.vue";
import DepthLimitReachedMessage from "../../common/DepthLimitReachedMessage.vue";
import { mapGetters } from "vuex";

export default {
    name: "ArtifactComparison",

    components: {
        CollapsableContent,
        DepthLimitReachedMessage,
        ArtifactLabel,
        FieldComparison,
        "artifacts-list-comparison": () => import("./ArtifactsListComparison.vue"),
    },

    props: {
        base: { require: true, type: Object },
        compared_to: { required: true, type: Object },
    },

    computed: {
        ...mapGetters({
            filterArtifacts: "comparison/filterArtifacts",
            findBaseArtifactsByIds: "comparison/base/findArtifactsByIds",
            isLimitReachedOnBaseArtifact: "comparison/base/isLimitReachedOnArtifact",
            findComparedToArtifactsByIds: "comparison/compared_to/findArtifactsByIds",
            isLimitReachedOnComparedToArtifact: "comparison/compared_to/isLimitReachedOnArtifact",
        }),
        base_linked_artifacts() {
            return this.findBaseArtifactsByIds(this.base.linked_artifact_ids);
        },
        compared_to_linked_artifacts() {
            return this.findComparedToArtifactsByIds(this.compared_to.linked_artifact_ids);
        },
        filtered_base_linked_artifacts() {
            return this.filterArtifacts(this.base_linked_artifacts);
        },
        filtered_compared_to_linked_artifacts() {
            return this.filterArtifacts(this.compared_to_linked_artifacts);
        },
        are_linked_artifacts_available() {
            return (
                this.base.linked_artifact_ids.length > 0 ||
                this.compared_to.linked_artifact_ids.length > 0
            );
        },
        is_depth_limit_reached() {
            return (
                this.isLimitReachedOnBaseArtifact(this.base) ||
                this.isLimitReachedOnComparedToArtifact(this.compared_to)
            );
        },
    },
};
</script>
