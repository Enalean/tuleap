<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
  -->

<template>
    <div
        class="modal fade hide"
        id="move-artifact-modal"
        role="dialog"
        aria-labelledby="modal-move-artifact-choose-trackers"
        aria-hidden="true"
    >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <a role="button" class="tuleap-modal-close close" data-dismiss="modal">
                        <i class="fas fa-times modal-close-icon" aria-hidden="true"></i>
                    </a>
                    <move-modal-title />
                </div>
                <div class="modal-body move-artifact-modal-body">
                    <div
                        v-if="is_loading_initial || is_processing_move"
                        class="move-artifact-loader"
                    ></div>
                    <div v-if="has_error" class="alert alert-error move-artifact-error">
                        {{ error_message }}
                    </div>
                    <move-modal-selectors v-show="!is_processing_move" />
                    <dry-run-preview v-if="has_processed_dry_run && !is_processing_move" />
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-dismiss="modal">
                        <translate>Close</translate>
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        v-on:click="moveDryRunArtifact()"
                        v-bind:disabled="has_no_selected_tracker || is_processing_move"
                        v-show="!has_processed_dry_run"
                        data-test="move-artifact"
                    >
                        <i class="fa fa-share"></i>
                        <translate>Move artifact</translate>
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        v-on:click="moveArtifact()"
                        v-bind:disabled="is_processing_move || !is_move_possible"
                        v-show="has_processed_dry_run"
                        data-test="confirm-move-artifact"
                    >
                        <i class="fa fa-check"></i>
                        <translate>Confirm</translate>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import $ from "jquery";
import MoveModalTitle from "./MoveModalTitle.vue";
import MoveModalSelectors from "./MoveModalSelectors.vue";
import DryRunPreview from "./DryRunPreview.vue";
import store from "../store/index.js";
import { getArtifactId } from "../from-tracker-presenter.js";

export default {
    name: "MoveModal",
    store,
    components: {
        DryRunPreview,
        MoveModalTitle,
        MoveModalSelectors,
    },
    computed: {
        ...mapState([
            "is_loading_initial",
            "error_message",
            "has_processed_dry_run",
            "selected_tracker",
            "is_processing_move",
            "is_move_possible",
        ]),
        ...mapGetters(["has_error"]),
        has_no_selected_tracker() {
            return this.selected_tracker.tracker_id === null;
        },
    },
    mounted() {
        const $modal = $(this.$el);
        $modal.on("show", () => {
            this.$store.dispatch("loadProjectList");
        });
        $modal.on("hidden", () => {
            this.$store.commit("resetState");
            this.$el.remove();
        });
        $modal.modal();
    },
    methods: {
        moveDryRunArtifact() {
            return this.$store.dispatch("moveDryRun", getArtifactId());
        },
        moveArtifact() {
            return this.$store.dispatch("move", getArtifactId());
        },
    },
};
</script>
