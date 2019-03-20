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
        <span class="baseline-content-artifact-label tlp-badge-primary tlp-badge-outline">
            {{ artifact.tracker_name }} #{{ artifact.id }}
        </span>

        <span data-test-type="artifact-title">
            {{ artifact.title }}
        </span>

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
            <template v-if="is_description_available">
                <h3 class="baseline-content-artifact-body-field-label" v-translate>
                    Description
                </h3>
                <p class="baseline-content-body-content" data-test-type="artifact-description">
                    {{ artifact.description }}
                </p>
            </template>
        </div>

        <baseline-artifacts-skeleton v-if="is_loading"/>

        <baseline-artifacts
            v-else-if="!is_loading_failed"
            v-bind:artifacts="linked_artifacts"
            v-bind:baseline_id="baseline_id"
        />
    </div>
</template>

<script>
import { getBaselineArtifactsByIds } from "../../api/rest-querier";
import BaselineArtifacts from "./BaselineArtifacts.vue";
import BaselineArtifactsSkeleton from "./BaselineArtifactsSkeleton.vue";

export default {
    name: "BaselineArtifact",

    components: { BaselineArtifacts, BaselineArtifactsSkeleton },

    props: {
        baseline_id: {
            type: Number,
            required: true
        },
        artifact: {
            type: Object,
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

        error_message_tooltip() {
            return this.$gettext("Cannot fetch linked artifacts");
        }
    },

    beforeCreate() {
        this.$options.components.BaselineArtifacts = BaselineArtifacts;
    },

    mounted() {
        if (this.artifact.linked_artifact_ids && this.artifact.linked_artifact_ids.length > 0) {
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
