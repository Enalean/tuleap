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
    <form
        class="tlp-modal"
        aria-labelledby="artidoc-configuration-modal-title"
        ref="modal_element"
        v-on:submit="onSubmit"
    >
        <configuration-modal-header v-bind:close_modal="closeModal" />

        <div class="tlp-modal-body">
            <introductory-text v-bind:configuration_helper="configuration_helper" />
            <tracker-selection
                v-bind:configuration_helper="configuration_helper"
                v-bind:disabled="is_success"
            />
        </div>

        <div class="tlp-modal-footer">
            <error-feedback v-if="is_error" v-bind:error_message="error_message" />
            <success-feedback v-if="is_success" />

            <div class="artidoc-modal-buttons">
                <button
                    type="button"
                    class="tlp-button-primary tlp-modal-action"
                    v-if="is_success"
                    v-on:click="closeModal"
                    data-test="close-modal-after-success"
                >
                    {{ $gettext("Close") }}
                </button>

                <template v-else>
                    <button
                        type="button"
                        class="tlp-button-primary tlp-button-outline tlp-modal-action"
                        v-on:click="closeModal"
                    >
                        {{ $gettext("Cancel") }}
                    </button>

                    <button
                        type="submit"
                        class="tlp-button-primary tlp-modal-action"
                        v-bind:disabled="is_submit_button_disabled"
                        data-test="submit"
                    >
                        <i
                            class="tlp-button-icon"
                            v-bind:class="submit_button_icon"
                            aria-hidden="true"
                        ></i>
                        {{ $gettext("Save configuration") }}
                    </button>
                </template>
            </div>
        </div>
    </form>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { ref, toRaw } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import IntroductoryText from "@/components/configuration/IntroductoryText.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import ConfigurationModalHeader from "@/components/configuration/ConfigurationModalHeader.vue";
import TrackerSelection from "@/components/configuration/TrackerSelection.vue";
import { useConfigurationScreenHelper } from "@/composables/useConfigurationScreenHelper";
import { OPEN_CONFIGURATION_MODAL_BUS } from "@/stores/useOpenConfigurationModalBusStore";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";

const { $gettext } = useGettext();

const configuration_helper = useConfigurationScreenHelper(strictInject(CONFIGURATION_STORE));

const { is_submit_button_disabled, submit_button_icon, is_success, is_error, error_message } =
    configuration_helper;

const modal_element = ref<HTMLElement | undefined>(undefined);

const noop = (): void => {};

let onSuccessfulSaveCallback: () => void = noop;

strictInject(OPEN_CONFIGURATION_MODAL_BUS).registerHandler(openModal);

let modal: Modal | null = null;
function openModal(onSuccessfulSaved?: () => void): void {
    onSuccessfulSaveCallback = onSuccessfulSaved || noop;
    if (modal === null && modal_element.value) {
        modal = createModal(toRaw(modal_element.value), { dismiss_on_backdrop_click: false });
    }

    if (modal) {
        configuration_helper.resetSelection();
        modal.show();
    }
}

function closeModal(): void {
    if (modal) {
        modal.hide();
    }

    if (is_success.value) {
        onSuccessfulSaveCallback();
    }
}

function onSubmit(event: Event): void {
    configuration_helper.onSubmit(event);
}
</script>

<style scoped lang="scss">
.tlp-modal-footer {
    flex-direction: column;
}

.artidoc-modal-buttons {
    display: flex;
    justify-content: flex-end;
    margin: var(--tlp-medium-spacing) 0 0;
}
</style>
