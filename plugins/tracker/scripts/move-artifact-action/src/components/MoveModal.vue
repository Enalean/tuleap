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
                        v-if="is_loading_initial || is_processing_move"
                        class="move-artifact-loader"
                    ></div>
                    <div
                        data-test="modal-error-message"
                        v-if="error_message.length > 0"
                        class="alert alert-error move-artifact-error"
                    >
                        {{ error_message }}
                    </div>
                    <move-modal-selectors v-show="!is_processing_move" />
                    <dry-run-preview v-if="has_processed_dry_run && !is_processing_move" />
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-dismiss="modal">
                        {{ $gettext("Close") }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        v-on:click="moveDryRun(artifact_id)"
                        v-bind:disabled="has_no_selected_tracker || is_processing_move"
                        v-show="!has_processed_dry_run"
                        data-test="move-artifact"
                    >
                        <i class="fa fa-share"></i>
                        {{ $gettext("Move artifact") }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        v-on:click="move(artifact_id)"
                        v-bind:disabled="is_processing_move || !is_move_possible"
                        v-show="has_processed_dry_run"
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
import { useState, useMutations, useActions } from "vuex-composition-helpers";
import $ from "jquery";
import { strictInject } from "@tuleap/vue-strict-inject";
import MoveModalTitle from "./MoveModalTitle.vue";
import MoveModalSelectors from "./MoveModalSelectors.vue";
import DryRunPreview from "./DryRunPreview.vue";
import type { RootState } from "../store/types";
import type { RootActions } from "../store/actions";
import type { RootMutations } from "../store/mutations";
import { ARTIFACT_ID } from "../injection-symbols";

const { $gettext } = useGettext();

const artifact_id = strictInject(ARTIFACT_ID);
const { resetState } = useMutations<Pick<RootMutations, "resetState">>(["resetState"]);
const { moveDryRun, move, loadProjectList } = useActions<
    Pick<RootActions, "moveDryRun" | "move" | "loadProjectList">
>(["moveDryRun", "move", "loadProjectList"]);
const {
    is_loading_initial,
    is_processing_move,
    is_move_possible,
    has_processed_dry_run,
    error_message,
    selected_tracker_id,
} = useState<
    Pick<
        RootState,
        | "is_loading_initial"
        | "is_processing_move"
        | "is_move_possible"
        | "has_processed_dry_run"
        | "error_message"
        | "selected_tracker_id"
    >
>([
    "is_loading_initial",
    "is_processing_move",
    "is_move_possible",
    "has_processed_dry_run",
    "error_message",
    "selected_tracker_id",
]);

const move_artifact_modal = ref<HTMLElement>();
const has_no_selected_tracker = computed(() => {
    return selected_tracker_id.value === null;
});

const isAnHTMLElement = (element: unknown): element is HTMLElement =>
    element instanceof HTMLElement;

onMounted(() => {
    if (!isAnHTMLElement(move_artifact_modal.value)) {
        return;
    }
    const $modal = $(move_artifact_modal.value);

    $modal.on("show", () => {
        loadProjectList();
    });
    $modal.on("hidden", () => {
        resetState();

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
