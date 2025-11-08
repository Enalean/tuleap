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
    <div
        role="dialog"
        aria-labelledby="remove-modal-label"
        class="tlp-modal tlp-modal-danger"
        ref="remove_modal_element"
    >
        <input
            type="hidden"
            v-bind:name="input_name"
            v-bind:value="subscriber_id.includes('_') ? subscriber_id.split('_')[1] : subscriber_id"
        />

        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="remove-modal-label">
                {{ $gettext("Notification deletion") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-on:click="$emit('cancel')"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p>
                {{ $gettext(`You are about to delete the notification for '${subscriber_name}'`) }}
            </p>
            <p>{{ $gettext("Please confirm your action.") }}</p>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                data-dismiss="modal"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                v-on:click="$emit('cancel')"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-danger tlp-modal-action"
                data-test="validate-notification-button"
            >
                {{ $gettext("Confirm deletion") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { useGettext } from "vue3-gettext";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";

const { $gettext } = useGettext();

defineProps<{
    subscriber_name: string;
    subscriber_id: string;
    input_name: string;
}>();

const remove_modal_element = ref<HTMLElement | null>(null);
const remove_modal = ref<Modal | null>(null);

defineEmits<{
    (e: "cancel"): void;
}>();

onMounted(() => {
    if (remove_modal_element.value) {
        remove_modal.value = createModal(remove_modal_element.value, {
            destroy_on_hide: true,
            dismiss_on_backdrop_click: false,
        });
        remove_modal.value.show();
    }
});

onUnmounted(() => {
    if (remove_modal.value) {
        remove_modal.value.destroy();
    }
});
</script>
