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
    <div class="baseline-content-artifact" data-test-type="artifact">
        <a
            v-on:click="toggleCollapse()"
            class="baseline-content-artifact-collapse-link"
            data-test-action="toggle-expand-collapse"
        >
            <i
                class="fa fa-fw"
                v-bind:class="{ 'fa-caret-right': is_collapsed, 'fa-caret-down': !is_collapsed }"
            ></i>
        </a>
        <artifact-label v-bind:artifact="artifact" class="baseline-content-artifact-label" />

        <div v-show="!is_collapsed">
            <div class="baseline-content-artifact-body" data-test-type="artifact-fields">
                <artifact-field
                    v-if="is_description_available"
                    semantic="description"
                    v-bind:tracker_id="artifact.tracker_id"
                    v-bind:value="artifact.description"
                    data-test-type="artifact-description"
                    v-bind:html_content="true"
                />
                <artifact-field
                    v-if="is_status_available"
                    semantic="status"
                    v-bind:tracker_id="artifact.tracker_id"
                    v-bind:value="artifact.status"
                    data-test-type="artifact-status"
                />
            </div>

            <depth-limit-reached-message v-if="is_limit_reached" />

            <artifacts-list
                v-else-if="filtered_linked_artifacts.length > 0"
                v-bind:artifacts="filtered_linked_artifacts"
            />
        </div>
    </div>
</template>

<script>
import ArtifactsList from "./ArtifactsList.vue";
import ArtifactLabel from "../common/ArtifactLabel.vue";
import ArtifactField from "./ArtifactField.vue";
import DepthLimitReachedMessage from "../common/DepthLimitReachedMessage.vue";
import { mapGetters } from "vuex";

export default {
    name: "ContentArtifact",

    components: {
        ArtifactLabel,
        ArtifactsList,
        ArtifactField,
        DepthLimitReachedMessage,
    },

    props: {
        artifact: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            is_collapsed: false,
        };
    },

    computed: {
        ...mapGetters("current_baseline", [
            "findArtifactsByIds",
            "isLimitReachedOnArtifact",
            "filterArtifacts",
        ]),
        is_description_available() {
            return this.artifact.description !== null && this.artifact.description.length > 0;
        },

        is_status_available() {
            return this.artifact.status !== null && this.artifact.status.length > 0;
        },

        linked_artifacts() {
            return this.findArtifactsByIds(this.artifact.linked_artifact_ids);
        },

        filtered_linked_artifacts() {
            return this.filterArtifacts(this.linked_artifacts);
        },

        is_limit_reached() {
            return this.isLimitReachedOnArtifact(this.artifact);
        },
    },

    beforeCreate() {
        this.$options.components.ArtifactsList = ArtifactsList;
    },

    methods: {
        toggleCollapse() {
            this.is_collapsed = !this.is_collapsed;
        },
    },
};
</script>
