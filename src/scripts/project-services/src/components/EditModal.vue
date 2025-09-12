<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <form
        method="post"
        v-bind:action="form_url"
        class="tlp-modal"
        role="dialog"
        aria-labelledby="project-admin-services-edit-modal-title"
        data-test="service-edit-modal"
        ref="form_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="project-admin-services-edit-modal-title">
                {{ $gettext("Edit service") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <slot name="content" />
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                data-test="save-service-modifications"
            >
                <i class="fa fa-save tlp-button-icon"></i>
                {{ $gettext("Save modifications") }}
            </button>
        </div>
    </form>
</template>
<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { createModal } from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";

defineProps<{
    form_url: string;
}>();

const emit = defineEmits<{
    (e: "reset-modal"): void;
}>();

const modal = ref<Modal | null>(null);
const form_element = ref<HTMLElement | null>(null);

onMounted(() => {
    if (!form_element.value) {
        return;
    }

    modal.value = createModal(form_element.value);
    modal.value.addEventListener("tlp-modal-hidden", () => {
        emit("reset-modal");
    });
});

onBeforeUnmount(() => {
    modal.value?.destroy();
});

function show(): void {
    modal.value?.show();
}

defineExpose({ show });
</script>
