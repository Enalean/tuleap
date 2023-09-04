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
        aria-labelledby="onlyoffice-admin-remove-project-confirmation-modal-title"
        ref="root"
    >
        <div class="tlp-modal-header">
            <h1
                class="tlp-modal-title"
                id="onlyoffice-admin-remove-project-confirmation-modal-title"
            >
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
                    $ngettext(
                        "%{ nb } project will have their access removed and won't be able to use this server:",
                        "%{ nb } projects will have their access removed and won't be able to use this server:",
                        nb,
                        { nb: String(nb) },
                    )
                }}
                <span class="tlp-badge-secondary">{{ server.server_url }}</span>
            </p>
            <p>
                {{ $gettext("All unsaved document modifications will be lost.") }}
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
            <button type="submit" class="tlp-button-danger tlp-modal-action" data-test="submit">
                {{ $gettext("Remove access") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import type { Server } from "../../../type";

defineProps<{
    nb: number;
    server: Server;
}>();

const root = ref<HTMLElement | null>(null);
const modal = ref<Modal | null>(null);

const emit = defineEmits<{ (e: "cancel-project-removal"): void }>();

function cancelProjectRemoval(): void {
    emit("cancel-project-removal");
}

onMounted(() => {
    if (root.value) {
        modal.value = createModal(root.value);
        modal.value.addEventListener(EVENT_TLP_MODAL_HIDDEN, cancelProjectRemoval);
        modal.value.show();
    }
});

onUnmounted(() => {
    if (modal.value) {
        modal.value.removeEventListener(EVENT_TLP_MODAL_HIDDEN, cancelProjectRemoval);
    }
});
</script>
