<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <section
        role="dialog"
        v-if="error"
        aria-labelledby="artidoc-global-error-modal-label"
        class="tlp-modal tlp-modal-danger"
        ref="modal_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="artidoc-global-error-modal-label">
                {{ $gettext("Error") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" role="img"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p>{{ error.message }}</p>
            <pre>{{ error.details }}</pre>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                data-dismiss="modal"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
            >
                {{ close }}
            </button>
        </div>
    </section>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { onMounted, onUnmounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import type { GlobalErrorMessage } from "@/global-error-message-injection-key";
import { SET_GLOBAL_ERROR_MESSAGE } from "@/global-error-message-injection-key";
import { strictInject } from "@tuleap/vue-strict-inject";

defineProps<{ error: GlobalErrorMessage | null }>();

const setGlobalErrorMessage = strictInject(SET_GLOBAL_ERROR_MESSAGE);

const { $gettext } = useGettext();

const close = $gettext("Close");

const modal_element = ref<HTMLElement>();

let modal: Modal | null = null;

onMounted(() => {
    if (!modal_element.value) {
        return;
    }

    modal = createModal(modal_element.value, {
        destroy_on_hide: true,
    });
    modal.addEventListener("tlp-modal-hidden", () => {
        setGlobalErrorMessage(null);
    });
    modal.show();
});

onUnmounted(() => {
    modal?.destroy();
});
</script>
