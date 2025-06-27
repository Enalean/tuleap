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
    <div
        class="tlp-modal"
        v-bind:class="{ 'artidoc-config-modal-with-fields': are_fields_enabled }"
        aria-labelledby="artidoc-configuration-modal-title"
        ref="modal_element"
    >
        <configuration-modal-header />
        <configuration-modal-tabs
            v-on:switch-configuration-tab="switchTab"
            v-bind:current_tab="current_tab"
        />
        <configure-tracker v-if="current_tab === TRACKER_SELECTION_TAB && is_modal_shown" />
        <configure-readonly-fields
            v-if="
                current_tab === READONLY_FIELDS_SELECTION_TAB &&
                is_tracker_configured &&
                is_modal_shown
            "
        />
    </div>
</template>

<script setup lang="ts">
import { computed, provide, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { OPEN_CONFIGURATION_MODAL_BUS } from "@/stores/useOpenConfigurationModalBusStore";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import { ARE_FIELDS_ENABLED } from "@/are-fields-enabled";
import ConfigurationModalHeader from "@/components/configuration/ConfigurationModalHeader.vue";
import ConfigureTracker from "@/components/configuration/ConfigureTracker.vue";
import ConfigureReadonlyFields from "@/components/configuration/ConfigureReadonlyFields.vue";
import type { ConfigurationTab } from "@/components/configuration/configuration-modal";
import {
    CLOSE_CONFIGURATION_MODAL,
    READONLY_FIELDS_SELECTION_TAB,
    TRACKER_SELECTION_TAB,
} from "@/components/configuration/configuration-modal";
import ConfigurationModalTabs from "@/components/configuration/ConfigurationModalTabs.vue";

const are_fields_enabled = strictInject(ARE_FIELDS_ENABLED);
const selected_tracker = strictInject(SELECTED_TRACKER);

const modal_element = ref<HTMLElement | undefined>(undefined);
const current_tab = ref<ConfigurationTab>(TRACKER_SELECTION_TAB);
const is_tracker_configured = computed(() => selected_tracker.value.isValue());

const noop = (): void => {};

let onSuccessfulSaveCallback = noop;

strictInject(OPEN_CONFIGURATION_MODAL_BUS).registerHandler(openModal);
provide(CLOSE_CONFIGURATION_MODAL, closeModal);

let modal: Modal | null = null;
const is_modal_shown = ref(false);

function openModal(onSuccessfulSaved?: () => void): void {
    onSuccessfulSaveCallback = onSuccessfulSaved ?? noop;
    if (!modal_element.value) {
        return;
    }
    if (!modal) {
        modal = createModal(modal_element.value, {
            dismiss_on_backdrop_click: false,
        });
    }
    modal.show();
    is_modal_shown.value = true;
}

function closeModal(is_success: boolean): void {
    modal?.hide();
    is_modal_shown.value = false;

    if (is_success) {
        onSuccessfulSaveCallback();
    }
}

function switchTab(tab: ConfigurationTab): void {
    current_tab.value = tab;
}
</script>

<style scoped lang="scss">
.artidoc-config-modal-with-fields > .tlp-modal-header {
    border-bottom: 0;
}
</style>
