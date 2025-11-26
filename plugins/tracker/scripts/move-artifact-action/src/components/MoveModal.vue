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
        class="tlp-modal"
        id="move-artifact-modal"
        role="dialog"
        aria-labelledby="modal-move-artifact-choose-trackers"
        ref="move_artifact_modal"
    >
        <div class="tlp-modal-header">
            <move-modal-title />
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <div
                data-test="modal-loader"
                v-if="selectors_store.are_projects_loading || modal_store.is_processing_move"
                class="tlp-form-element"
            >
                <label class="tlp-label tlp-skeleton-text"></label>
                <input type="text" class="tlp-input tlp-skeleton-field" disabled />
            </div>
            <div
                v-if="modal_store.error_message.length > 0"
                data-test="modal-error-message"
                class="tlp-alert-danger"
            >
                {{ modal_store.error_message }}
            </div>
            <move-modal-selectors v-show="!modal_store.is_processing_move" />

            <div
                class="dry-run-feedbacks"
                v-if="dry_run_store.has_processed_dry_run && !modal_store.is_processing_move"
            >
                <dry-run-not-migrated-field-state />
                <dry-run-partially-migrated-field-state />
                <dry-run-fully-migrated-field-state />
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Close") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                v-on:click="modal_store.moveDryRun(artifact_id)"
                v-bind:disabled="has_no_selected_tracker || modal_store.is_processing_move"
                v-show="!dry_run_store.has_processed_dry_run"
                data-test="move-artifact"
            >
                <i class="fa-solid fa-share" aria-hidden="true"></i>
                {{ $gettext("Move artifact") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                v-on:click="modal_store.move(artifact_id)"
                v-bind:disabled="modal_store.is_processing_move || !dry_run_store.is_move_possible"
                v-show="dry_run_store.has_processed_dry_run"
                data-test="confirm-move-artifact"
            >
                <i class="fa-solid fa-check" aria-hidden="true"></i>
                {{ $gettext("Confirm") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref, onBeforeUnmount } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { useSelectorsStore } from "../stores/selectors";
import { useDryRunStore } from "../stores/dry-run";
import { useModalStore } from "../stores/modal";
import { ARTIFACT_ID } from "../injection-symbols";
import MoveModalSelectors from "./MoveModalSelectors.vue";
import DryRunNotMigratedFieldState from "./DryRunNotMigratedFieldState.vue";
import DryRunPartiallyMigratedFieldState from "./DryRunPartiallyMigratedFieldState.vue";
import DryRunFullyMigratedFieldState from "./DryRunFullyMigratedFieldState.vue";
import MoveModalTitle from "./MoveModalTitle.vue";

const { $gettext } = useGettext();

const artifact_id = strictInject(ARTIFACT_ID);

const selectors_store = useSelectorsStore();
const dry_run_store = useDryRunStore();
const modal_store = useModalStore();

const move_artifact_modal = ref<HTMLElement>();
const modal = ref<Modal | null>(null);
const has_no_selected_tracker = computed(() => {
    return selectors_store.selected_tracker_id === null;
});

onMounted(() => {
    if (!move_artifact_modal.value) {
        return;
    }

    modal.value = createModal(move_artifact_modal.value, {
        destroy_on_hide: true,
        dismiss_on_backdrop_click: false,
    });
    modal.value.show();
    selectors_store.loadProjectList();
});

onBeforeUnmount(() => {
    modal.value?.destroy();
});
</script>
<style scoped lang="scss">
.dry-run-feedbacks {
    margin: var(--tlp-medium-spacing) 0 0;
}
</style>
