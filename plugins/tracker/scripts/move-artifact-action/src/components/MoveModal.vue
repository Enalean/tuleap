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
        ref="move_artifact_modal"
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
                        data-test="modal-loader"
                        v-if="
                            selectors_store.are_projects_loading || modal_store.is_processing_move
                        "
                        class="move-artifact-loader"
                    ></div>
                    <div
                        data-test="modal-error-message"
                        v-if="modal_store.error_message.length > 0"
                        class="alert alert-error move-artifact-error"
                    >
                        {{ modal_store.error_message }}
                    </div>
                    <move-modal-selectors v-show="!modal_store.is_processing_move" />
                    <dry-run-preview
                        v-if="
                            dry_run_store.has_processed_dry_run && !modal_store.is_processing_move
                        "
                    />
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-dismiss="modal">
                        {{ $gettext("Close") }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        v-on:click="modal_store.moveDryRun(artifact_id)"
                        v-bind:disabled="has_no_selected_tracker || modal_store.is_processing_move"
                        v-show="!dry_run_store.has_processed_dry_run"
                        data-test="move-artifact"
                    >
                        <i class="fa fa-share"></i>
                        {{ $gettext("Move artifact") }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        v-on:click="modal_store.move(artifact_id)"
                        v-bind:disabled="
                            modal_store.is_processing_move || !dry_run_store.is_move_possible
                        "
                        v-show="dry_run_store.has_processed_dry_run"
                        data-test="confirm-move-artifact"
                    >
                        <i class="fa fa-check"></i>
                        {{ $gettext("Confirm") }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import $ from "jquery";
import { strictInject } from "@tuleap/vue-strict-inject";
import { useSelectorsStore } from "../stores/selectors";
import { useDryRunStore } from "../stores/dry-run";
import { useModalStore } from "../stores/modal";
import { ARTIFACT_ID } from "../injection-symbols";
import MoveModalTitle from "./MoveModalTitle.vue";
import MoveModalSelectors from "./MoveModalSelectors.vue";
import DryRunPreview from "./DryRunPreview.vue";

const { $gettext } = useGettext();

const artifact_id = strictInject(ARTIFACT_ID);

const selectors_store = useSelectorsStore();
const dry_run_store = useDryRunStore();
const modal_store = useModalStore();

const move_artifact_modal = ref<HTMLElement>();
const has_no_selected_tracker = computed(() => {
    return selectors_store.selected_tracker_id === null;
});

const isAnHTMLElement = (element: unknown): element is HTMLElement =>
    element instanceof HTMLElement;

onMounted(() => {
    if (!isAnHTMLElement(move_artifact_modal.value)) {
        return;
    }
    const $modal = $(move_artifact_modal.value);

    $modal.on("show", () => {
        selectors_store.loadProjectList();
    });
    $modal.on("hidden", () => {
        selectors_store.$reset();
        dry_run_store.$reset();
        modal_store.$reset();

        if (!isAnHTMLElement(move_artifact_modal.value)) {
            return;
        }

        move_artifact_modal.value.remove();
    });

    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    $modal.modal();
});
</script>
