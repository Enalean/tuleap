<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <div ref="modal_element" role="dialog" aria-labelledby="modal-label" class="tlp-modal">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-label">
                {{ $gettext("Loading all the data") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                aria-label="Close"
                v-on:click="$emit('should-load-all', false)"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p>
                {{
                    $gettext(
                        "By clicking the 'Load all' button, all available data will be loaded and displayed in the table. This may result in slower performance or temporary latency, especially if the dataset is large.",
                    )
                }}
            </p>
            <p>{{ $gettext("Are you sure you want to continue ?") }}</p>
        </div>
        <div class="tlp-modal-footer">
            <button
                id="button-close"
                type="button"
                data-dismiss="modal"
                data-test="modal-cancel-button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                v-on:click="$emit('should-load-all', false)"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                data-test="modal-action-button"
                v-on:click="$emit('should-load-all', true)"
            >
                {{ $gettext("Load all") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";

const modal_element = ref<HTMLDivElement>();
const modal = ref<Modal | null>(null);

defineEmits<{
    (e: "should-load-all", should_load_all: boolean): void;
}>();

onMounted(() => {
    if (modal_element.value === undefined) {
        throw Error("Cannot find the modal html element");
    }
    modal.value = createModal(modal_element.value, {
        keyboard: false,
        dismiss_on_backdrop_click: true,
    });

    modal.value?.show();
});

onUnmounted(() => {
    modal.value?.destroy();
});
</script>
