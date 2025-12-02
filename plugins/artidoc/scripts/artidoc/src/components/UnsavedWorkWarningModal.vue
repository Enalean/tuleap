<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
        ref="modal_element"
        role="dialog"
        aria-labelledby="unsaved-work-warning-modal"
        class="tlp-modal tlp-modal-warning"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="unsaved-work-warning-modal">
                {{ $gettext("Wait a minute…") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                data-test="close-modal-button"
                v-bind:aria-label="$gettext('Close')"
                v-on:click="$emit('cancel')"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <h2 class="tlp-modal-subtitle">{{ $gettext("You have currently unsaved work") }}</h2>
            <p>
                {{
                    $gettext(
                        "You may want to save it before displaying an older version of the document. Do you want to display it anyway and lose your unsaved work?",
                    )
                }}
            </p>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                data-dismiss="modal"
                class="tlp-button-warning tlp-button-outline tlp-modal-action"
                v-on:click="$emit('cancel')"
                data-test="cancel-button"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                data-dismiss="modal"
                class="tlp-button-warning tlp-modal-action"
                v-on:click="$emit('continue-anyway')"
                data-test="continue-anyway-button"
            >
                {{ $gettext("Continue anyway") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { createModal } from "@tuleap/tlp-modal";

const modal_element = ref<HTMLElement | undefined>();

defineEmits<{
    (e: "cancel"): void;
    (e: "continue-anyway"): void;
}>();

onMounted(() => {
    if (!(modal_element.value instanceof HTMLElement)) {
        return;
    }

    createModal(modal_element.value, {
        dismiss_on_backdrop_click: false,
        destroy_on_hide: true,
    }).show();
});
</script>
