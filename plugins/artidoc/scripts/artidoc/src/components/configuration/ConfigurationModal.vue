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
    <button
        class="tlp-button-primary tlp-button-ellipsis"
        type="button"
        v-bind:title="button_title"
        v-on:click="openModal"
    >
        <i class="fa-solid fa-cog" role="img"></i>
    </button>

    <form
        class="tlp-modal"
        aria-labelledby="artidoc-configuration-modal-title"
        ref="modal_element"
        v-on:submit="onSubmit"
    >
        <configuration-modal-header />

        <div class="tlp-modal-body">
            <introductory-text />
            <div
                class="tlp-form-element"
                v-bind:class="{ 'tlp-form-element-error': no_allowed_trackers }"
                data-test="artidoc-configuration-modal-form-element-trackers"
            >
                <label class="tlp-label" for="artidoc-configuration-modal-tracker">
                    {{ $gettext("Tracker") }}
                    <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
                </label>
                <select
                    id="artidoc-configuration-modal-tracker"
                    class="tlp-select tlp-select-adjusted"
                    required
                    v-model="new_selected_tracker"
                >
                    <option v-bind:value="NO_SELECTED_TRACKER" disabled>
                        {{ $gettext("Choose a tracker") }}
                    </option>
                    <option
                        v-for="tracker in allowed_trackers"
                        v-bind:key="tracker.id"
                        v-bind:value="tracker.id"
                    >
                        {{ tracker.label }}
                    </option>
                </select>
                <p class="tlp-text-danger" v-if="no_allowed_trackers">
                    {{ $gettext("There isn't any suitable trackers in this project") }}
                    <i class="fa-regular fa-face-frown" aria-hidden="true"></i>
                </p>
            </div>
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
import { strictInject } from "@tuleap/vue-strict-inject";
import { useGettext } from "vue3-gettext";
import { computed, ref, toRaw } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import type { ConfigurationStore } from "@/stores/configuration-store";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import IntroductoryText from "@/components/configuration/IntroductoryText.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import ConfigurationModalHeader from "@/components/configuration/ConfigurationModalHeader.vue";

const NO_SELECTED_TRACKER = "0";

const { $gettext } = useGettext();

const {
    allowed_trackers,
    selected_tracker_id,
    is_saving,
    is_error,
    is_success,
    error_message,
    saveConfiguration,
    resetSuccessFlagFromPreviousCalls,
} = strictInject<ConfigurationStore>(CONFIGURATION_STORE);

const new_selected_tracker = ref(String(selected_tracker_id.value));

const no_allowed_trackers = allowed_trackers.length === 0;

const is_submit_button_disabled = computed(
    () =>
        no_allowed_trackers ||
        is_saving.value ||
        new_selected_tracker.value === NO_SELECTED_TRACKER ||
        new_selected_tracker.value === String(selected_tracker_id.value),
);
const submit_button_icon = computed(() =>
    is_saving.value ? "fa-solid fa-spin fa-circle-notch" : "fa-solid fa-floppy-disk",
);

const button_title = $gettext("Configure document");

const modal_element = ref<HTMLElement | undefined>(undefined);

let modal: Modal | null = null;
function openModal(): void {
    if (modal === null && modal_element.value) {
        modal = createModal(toRaw(modal_element.value));
    }

    if (modal) {
        new_selected_tracker.value = String(selected_tracker_id.value);
        resetSuccessFlagFromPreviousCalls();
        modal.show();
    }
}

function closeModal(): void {
    if (modal) {
        modal.hide();
    }
}

function onSubmit(event: Event): void {
    event.preventDefault();

    saveConfiguration(Number.parseInt(new_selected_tracker.value, 10));
}
</script>

<style scoped lang="scss">
.tlp-button-ellipsis {
    flex-shrink: 0;
    margin: var(--tlp-medium-spacing) 0 0;
    font-size: 1.125rem;
}

.tlp-modal-footer {
    flex-direction: column;
}

.artidoc-modal-buttons {
    display: flex;
    justify-content: flex-end;
    margin: var(--tlp-medium-spacing) 0 0;
}
</style>
