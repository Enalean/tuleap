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

        <depth-limit-reached-message v-if="artifact.is_depth_limit_reached"/>

        <artifacts-list
            v-else-if="artifact.linked_artifacts.length > 0"
            v-bind:artifacts="artifact.linked_artifacts"
        />
    </div>
</template>

<script>
import ArtifactsList from "./ArtifactsList.vue";
import ArtifactLabel from "../common/ArtifactLabel.vue";
import Field from "./Field.vue";
import DepthLimitReachedMessage from "../common/DepthLimitReachedMessage.vue";

export default {
    name: "Artifact",

    components: {
        ArtifactLabel,
        ArtifactsList,
        Field,
        DepthLimitReachedMessage
    },

    props: {
        artifact: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
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
        }
    },

    beforeCreate() {
        this.$options.components.ArtifactsList = ArtifactsList;
    }
};
</script>
