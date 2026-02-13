<!--
  - Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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
        role="dialog"
        aria-labelledby="refresh-after-error-title"
        class="tlp-modal tlp-modal-danger"
        ref="modal_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="refresh-after-error-title">
                {{ $gettext("Oops, an error occurred!") }}
            </h1>
        </div>
        <div class="tlp-modal-body">
            <p>
                {{
                    $gettext(
                        "An error occurred during one of your last actions. Please refresh the page to get back in a consistent state.",
                    )
                }}
            </p>
            <div class="tlp-alert-danger">{{ fault }}</div>
        </div>
        <div class="tlp-modal-footer">
            <button type="button" class="tlp-button-danger tlp-modal-action" v-on:click="refresh">
                {{ $gettext("Refresh") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { onMounted, onBeforeUnmount, useTemplateRef } from "vue";
import { useGettext } from "vue3-gettext";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import type { Fault } from "@tuleap/fault";

const { $gettext } = useGettext();

const modal_element = useTemplateRef<HTMLElement>("modal_element");
let modal: Modal | null = null;

defineProps<{
    fault: Fault;
}>();

const refresh = (): void => {
    window.location.reload();
};

onMounted(() => {
    if (modal_element.value) {
        modal = createModal(modal_element.value, {
            dismiss_on_backdrop_click: false,
            keyboard: false,
        });
        modal.show();
    }
});

onBeforeUnmount(() => {
    modal?.destroy();
});
</script>
