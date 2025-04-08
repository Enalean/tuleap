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
        v-bind:class="{ 'artidoc-config-modal-with-fields': are_fields_enabled }"
        aria-labelledby="artidoc-configuration-modal-title"
        ref="modal_element"
        v-on:submit="onSubmit"
    >
        <configuration-modal-header v-bind:close_modal="closeModal" />

        <nav class="tlp-tabs" v-if="are_fields_enabled">
            <button
                class="tlp-tab"
                type="button"
                v-bind:class="{ 'tlp-tab-active': current_tab === 'tracker' }"
                v-on:click="current_tab = 'tracker'"
            >
                {{ $gettext("Tracker selection") }}
            </button>
            <button
                class="tlp-tab"
                type="button"
                v-bind:disabled="!is_tracker_configured"
                v-bind:class="getFieldsTabClasses()"
                v-bind:data-tlp-tooltip="$gettext('Please select a tracker first')"
                v-on:click="switchToFieldsSelection()"
                data-test="tab-fields"
            >
                {{ $gettext("Fields selection") }}
            </button>
        </nav>

        <div class="tlp-modal-body" v-if="current_tab === 'tracker'">
            <tracker-selection-introductory-text
                v-bind:configuration_helper="configuration_helper"
            />
            <tracker-selection
                v-bind:configuration_helper="configuration_helper"
                v-bind:is_tracker_selection_disabled="is_success"
            />
        </div>

        <div class="tlp-modal-body" v-else-if="configuration_store.selected_tracker.value !== null">
            <fields-selection-introductory-text
                v-bind:tracker="configuration_store.selected_tracker.value"
            />
            <fields-selection v-bind:selected_fields="configuration_store.selected_fields" />
        </div>

        <div class="tlp-modal-footer">
            <error-feedback v-if="is_error" v-bind:error_message="error_message" />
            <success-feedback v-if="is_success && current_tab === 'tracker'" />

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
import { computed, ref, toRaw } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import TrackerSelectionIntroductoryText from "@/components/configuration/TrackerSelectionIntroductoryText.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import ConfigurationModalHeader from "@/components/configuration/ConfigurationModalHeader.vue";
import TrackerSelection from "@/components/configuration/TrackerSelection.vue";
import FieldsSelection from "@/components/configuration/FieldsSelection.vue";
import { useConfigurationScreenHelper } from "@/composables/useConfigurationScreenHelper";
import { OPEN_CONFIGURATION_MODAL_BUS } from "@/stores/useOpenConfigurationModalBusStore";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { ARE_FIELDS_ENABLED } from "@/are-fields-enabled";
import FieldsSelectionIntroductoryText from "@/components/configuration/FieldsSelectionIntroductoryText.vue";

const { $gettext } = useGettext();

const are_fields_enabled = strictInject(ARE_FIELDS_ENABLED);
const configuration_store = strictInject(CONFIGURATION_STORE);
const configuration_helper = useConfigurationScreenHelper(configuration_store);

const { is_submit_button_disabled, submit_button_icon, is_success, is_error, error_message } =
    configuration_helper;

const modal_element = ref<HTMLElement | undefined>(undefined);
const current_tab = ref<"tracker" | "fields">("tracker");

const is_tracker_configured = computed(() => {
    return configuration_store.selected_tracker.value !== null;
});

const noop = (): void => {};

let onSuccessfulSaveCallback: () => void = noop;

strictInject(OPEN_CONFIGURATION_MODAL_BUS).registerHandler(openModal);

let modal: Modal | null = null;

function getFieldsTabClasses(): string[] {
    return [
        is_tracker_configured.value ? "" : "tlp-tab-disabled tlp-tooltip tlp-tooltip-right",
        current_tab.value === "fields" ? "tlp-tab-active" : "",
    ];
}

function switchToFieldsSelection(): void {
    if (is_tracker_configured.value) {
        current_tab.value = "fields";
    }
}

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

.artidoc-config-modal-with-fields > .tlp-modal-header {
    border-bottom: 0;
}

.tlp-tab.tlp-tab-disabled {
    opacity: 1;

    > .artidoc-configuration-tab-label {
        opacity: 0.5;
    }
}

.tlp-tooltip::before {
    text-transform: none;
}
</style>
