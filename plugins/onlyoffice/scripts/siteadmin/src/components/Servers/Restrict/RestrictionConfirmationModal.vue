<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
    <div
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="onlyoffice-admin-restrict-confirmation-modal-title"
        ref="root"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="onlyoffice-admin-restrict-confirmation-modal-title">
                {{ $gettext("Data may be lost") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fa-solid fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p>
                {{
                    $gettext(
                        "You are about to disallow access to the server for every projects on the platform.",
                    )
                }}
                {{
                    $gettext(
                        "This will induce loosing of modifications for users that are currently using it.",
                    )
                }}
            </p>
            <p>{{ $gettext("Please confirm your action.") }}</p>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-danger tlp-modal-action"
                v-on:click="is_submitting = true"
                data-test="submit"
            >
                <i
                    class="tlp-button-icon fa-solid fa-spin fa-circle-notch"
                    aria-hidden="true"
                    v-if="is_submitting"
                    data-test="submit-icon"
                ></i>
                {{ $gettext("Disallow for all projects") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";

const root = ref<HTMLElement | null>(null);
const modal = ref<Modal | null>(null);

const is_submitting = ref(false);

const emit = defineEmits<{ (e: "cancel-restriction"): void }>();

function cancelRestriction(): void {
    emit("cancel-restriction");
}

onMounted(() => {
    if (root.value) {
        modal.value = createModal(root.value);
        modal.value.addEventListener(EVENT_TLP_MODAL_HIDDEN, cancelRestriction);
        modal.value.show();
    }
});

onUnmounted(() => {
    if (modal.value) {
        modal.value.removeEventListener(EVENT_TLP_MODAL_HIDDEN, cancelRestriction);
    }
});
</script>
