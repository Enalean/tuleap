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
    <div class="baseline-content-artifact" data-test-type="artifact">
        <artifact-label v-bind:artifact="artifact" class="baseline-content-artifact-label"/>

        <span
            v-if="is_loading_failed"
            class="tlp-tooltip tlp-tooltip-right baseline-content-artifact-error-message"
            v-bind:data-tlp-tooltip="error_message_tooltip"
        >
            <i
                class="fa fa-exclamation-circle baseline-tooltip-icon"
            ></i>
        </span>

        <div class="baseline-content-artifact-body" data-test-type="artifact-fields">
            <field
                v-if="is_description_available"
                semantic="description"
                v-bind:tracker_id="artifact.tracker_id"
                v-bind:value="artifact.description"
                data-test-type="artifact-description"
                v-bind:html_content="true"
            />
            <field
                v-if="is_status_available"
                semantic="status"
                v-bind:tracker_id="artifact.tracker_id"
                v-bind:value="artifact.status"
                data-test-type="artifact-status"
            />
        </div>

        <artifacts-list-skeleton v-if="is_loading"/>

        <baseline-depth-limit-reached-message v-else-if="is_depth_limit_reached && are_linked_artifact_ids_available"/>

        <artifacts-list
            v-else-if="!is_loading_failed"
            v-bind:current_depth="current_depth + 1"
            v-bind:artifacts="linked_artifacts"
            v-bind:baseline_id="baseline_id"
        />
    </div>
</template>

<script>
import { getBaselineArtifactsByIds } from "../../api/rest-querier";
import ArtifactsList from "./ArtifactsList.vue";
import ArtifactsListSkeleton from "./ArtifactsListSkeleton.vue";
import ArtifactLabel from "../common/ArtifactLabel.vue";
import Field from "./Field.vue";
import { ARTIFACTS_EXPLORATION_DEPTH_LIMIT } from "../../constants/index";
import BaselineDepthLimitReachedMessage from "../common/BaselineDepthLimitReachedMessage.vue";

export default {
    name: "Artifact",

    components: {
        ArtifactLabel,
        ArtifactsList,
        ArtifactsListSkeleton,
        Field,
        BaselineDepthLimitReachedMessage
    },

    props: {
        baseline_id: {
            type: Number,
            required: true
        },
        artifact: {
            type: Object,
            required: true
        },
        current_depth: {
            type: Number,
            required: true
        }
    },

    data() {
        return {
            linked_artifacts: null,
            is_loading: true,
            is_loading_failed: false
        };
    },

    computed: {
        is_description_available() {
            return this.artifact.description !== null && this.artifact.description.length > 0;
        },

        is_status_available() {
            return this.artifact.status !== null && this.artifact.status.length > 0;
        },

        error_message_tooltip() {
            return this.$gettext("Cannot fetch linked artifacts");
        },

        is_depth_limit_reached() {
            return this.current_depth >= ARTIFACTS_EXPLORATION_DEPTH_LIMIT;
        },

        are_linked_artifact_ids_available() {
            return (
                this.artifact.linked_artifact_ids !== null &&
                this.artifact.linked_artifact_ids.length > 0
            );
        }
    },

    beforeCreate() {
        this.$options.components.ArtifactsList = ArtifactsList;
    },

    mounted() {
        if (this.are_linked_artifact_ids_available && !this.is_depth_limit_reached) {
            this.fetchLinkedArtifacts();
        } else {
            this.linked_artifacts = [];
            this.is_loading = false;
        }
    },

    methods: {
        async fetchLinkedArtifacts() {
            try {
                this.linked_artifacts = await getBaselineArtifactsByIds(
                    this.baseline_id,
                    this.artifact.linked_artifact_ids
                );
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>
