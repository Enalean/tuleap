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
  -->

<template>
    <div
        ref="modal_element"
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="pull-request-homepage-error-modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="pull-request-homepage-error-modal">
                {{ $gettext("Oh snap!") }}
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
            <p>{{ $gettext("An error occurred while doing one of your last actions.") }}</p>
            <p>
                <button
                    v-on:click="showErrorDetails()"
                    type="button"
                    class="tlp-button-primary tlp-button-outline tlp-button-small"
                    data-test="pull-request-homepage-error-modal-show-details"
                >
                    {{ $gettext("Show details") }}
                </button>
            </p>
            <div
                v-if="are_error_details_shown"
                data-test="pull-request-homepage-error-modal-details"
            >
                <h4>{{ $gettext("Error details") }}</h4>
                <pre data-test="pull-request-homepage-error-modal-details-message">{{
                    props.fault
                }}</pre>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button class="tlp-button-danger tlp-modal-action" data-dismiss="modal">
                {{ $gettext("OK") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { watch, ref, onMounted } from "vue";
import type { Fault } from "@tuleap/fault";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const props = defineProps<{
    fault: Fault | null;
}>();

const modal_element = ref<Element | undefined>();
const modal_instance = ref<Modal | null>(null);
const are_error_details_shown = ref(false);

function hideErrorDetails(): void {
    are_error_details_shown.value = false;
}

onMounted(() => {
    if (modal_element.value) {
        modal_instance.value = createModal(modal_element.value, {
            destroy_on_hide: false,
            keyboard: false,
        });

        modal_instance.value.addEventListener(EVENT_TLP_MODAL_HIDDEN, hideErrorDetails);
    }
});

watch(
    () => props.fault,
    () => {
        if (
            props.fault === null ||
            modal_instance.value === null ||
            modal_instance.value.is_shown
        ) {
            return;
        }

        modal_instance.value.show();
    },
);

function showErrorDetails(): void {
    are_error_details_shown.value = true;
}
</script>
