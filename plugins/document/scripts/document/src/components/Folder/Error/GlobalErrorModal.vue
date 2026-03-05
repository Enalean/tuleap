<!--
  - Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="document-error-modal-title"
        ref="root_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-error-modal-title">
                {{ $gettext("Oops, there's an issue.") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p>{{ $gettext("It seems an action you tried to perform can't be done.") }}</p>
            <template v-if="error_message !== null && error_message !== ''">
                <a
                    v-if="!is_more_shown"
                    class="document-error-modal-link"
                    v-on:click="is_more_shown = true"
                    data-test="show-details"
                >
                    {{ $gettext("Show error details") }}
                </a>
                <pre v-if="is_more_shown" data-test="details">{{ error_message }}</pre>
            </template>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Close") }}
            </button>
            <button
                type="button"
                class="tlp-button-danger tlp-modal-action"
                data-test="reload"
                v-on:click="reloadPage"
            >
                <i class="fa-solid fa-rotate tlp-button-icon"></i>
                {{ $gettext("Reload the page") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { onMounted, onUnmounted, ref } from "vue";
import emitter from "../../../helpers/emitter";
import type { Fault } from "@tuleap/fault";

const error_message = ref<string | null>(null);
const is_more_shown = ref(false);
const root_element = ref<InstanceType<typeof HTMLElement>>();
const modal = ref<Modal | null>(null);

onMounted((): void => {
    if (root_element.value) {
        modal.value = createModal(root_element.value);
        modal.value.addEventListener("tlp-modal-hidden", reset);

        emitter.on("global-modal-error", onGlobalModalError);
    }
});

onUnmounted(() => {
    emitter.off("global-modal-error", onGlobalModalError);
});

function onGlobalModalError(fault: Fault): void {
    error_message.value = fault.toString();

    modal.value?.show();
}

function reset() {
    error_message.value = null;
    is_more_shown.value = false;
}

function reloadPage(): void {
    window.location.reload();
}
</script>
