<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <div class="modal fade hide"
         id="move-artifact-modal"
         tabindex="-1"
         role="dialog"
         aria-labelledby="modal-move-artifact-choose-trackers"
         aria-hidden="true"
         ref="vuemodal"
    >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="tuleap-modal-close close" data-dismiss="modal">×</i>
                    <move-modal-title />
                </div>
                <div class="modal-body move-artifact-modal-body">
                    <div v-if="isLoadingInitial || is_processing_move" class="move-artifact-loader"></div>
                    <div v-if="hasError" class="alert alert-error move-artifact-error">{{ getErrorMessage }}</div>
                    <move-modal-selectors v-show="! is_processing_move" />
                    <dry-run-preview v-if="hasProcessedDryRun && ! is_processing_move" />
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-dismiss="modal"><translate>Close</translate></button>
                    <button type="button"
                            class="btn btn-primary"
                            v-on:click="moveDryRunArtifact()"
                            v-bind:disabled="hasNoSelectedTracker || is_processing_move"
                            v-show="! hasProcessedDryRun"
                    >
                        <i class="icon-share-alt"></i> <translate>Move artifact</translate>
                    </button>
                    <button type="button"
                            class="btn btn-primary"
                            v-on:click="moveArtifact()"
                            v-bind:disabled="is_processing_move"
                            v-show="hasProcessedDryRun"
                    >
                        <i class="icon-ok"></i> <translate>Confirm</translate>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import MoveModalTitle from "./MoveModalTitle.vue";
import MoveModalSelectors from "./MoveModalSelectors.vue";
import DryRunPreview from "./DryRunPreview.vue";
import store from "../store/index.js";
import { mapGetters, mapState } from "vuex";
import $ from "jquery";
import { getArtifactId } from "../from-tracker-presenter.js";

export default {
    name: "MoveModal",
    store,
    components: {
        DryRunPreview,
        MoveModalTitle,
        MoveModalSelectors
    },
    data() {
        return {
            is_processing_move: false
        };
    },
    computed: {
        ...mapState({
            isLoadingInitial: state => state.is_loading_initial,
            getErrorMessage: state => state.error_message,
            hasNoSelectedTracker: state => state.selected_tracker.tracker_id === null,
            getSelectedTrackerId: state => state.selected_tracker.tracker_id,
            hasProcessedDryRun: state => state.has_processed_dry_run,
            shouldRedirect: state => state.should_redirect
        }),
        ...mapGetters(["hasError"])
    },
    mounted() {
        $(this.$refs.vuemodal).on("show", () => {
            this.$store.dispatch("loadProjectList");
        });
        $(this.$refs.vuemodal).on("hidden", () => {
            this.$store.commit("resetState");
        });
    },
    methods: {
        async moveDryRunArtifact() {
            this.is_processing_move = true;
            await this.$store.dispatch("moveDryRun", [getArtifactId(), this.getSelectedTrackerId]);

            if (this.shouldRedirect) {
                window.location.href = "/plugins/tracker/?aid=" + getArtifactId();
            }
            this.is_processing_move = false;
        },
        async moveArtifact() {
            this.is_processing_move = true;
            await this.$store.dispatch("move", [getArtifactId(), this.getSelectedTrackerId]);
            if (this.getErrorMessage.length === 0) {
                window.location.href = "/plugins/tracker/?aid=" + getArtifactId();
            } else {
                this.is_processing_move = false;
            }
        }
    }
};
</script>
