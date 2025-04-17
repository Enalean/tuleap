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
        <configuration-modal-header v-bind:close_modal="closeModal" />
        <configuration-modal-tabs
            v-on:switch-configuration-tab="switchTab"
            v-bind:current_tab="current_tab"
            v-bind:selected_tracker="configuration_store.selected_tracker.value"
        />
        <configure-tracker
            v-if="current_tab === TRACKER_SELECTION_TAB"
            v-bind:configuration_helper="configuration_helper"
        />
        <configure-readonly-fields
            v-else-if="configuration_store.selected_tracker.value !== null"
            v-bind:configuration_helper="configuration_helper"
            v-bind:selected_tracker="configuration_store.selected_tracker.value"
            v-bind:selected_fields="configuration_store.selected_fields.value"
            v-bind:available_fields="configuration_store.available_fields.value"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, toRaw, provide } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { useConfigurationScreenHelper } from "@/composables/useConfigurationScreenHelper";
import { OPEN_CONFIGURATION_MODAL_BUS } from "@/stores/useOpenConfigurationModalBusStore";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { ARE_FIELDS_ENABLED } from "@/are-fields-enabled";

import ConfigurationModalHeader from "@/components/configuration/ConfigurationModalHeader.vue";
import ConfigureTracker from "@/components/configuration/ConfigureTracker.vue";
import ConfigureReadonlyFields from "@/components/configuration/ConfigureReadonlyFields.vue";
import {
    CLOSE_CONFIGURATION_MODAL,
    TRACKER_SELECTION_TAB,
} from "@/components/configuration/configuration-modal";
import type { ConfigurationTab } from "@/components/configuration/configuration-modal";
import ConfigurationModalTabs from "@/components/configuration/ConfigurationModalTabs.vue";

const are_fields_enabled = strictInject(ARE_FIELDS_ENABLED);
const configuration_store = strictInject(CONFIGURATION_STORE);
const configuration_helper = useConfigurationScreenHelper(configuration_store);

const modal_element = ref<HTMLElement | undefined>(undefined);
const current_tab = ref<ConfigurationTab>(TRACKER_SELECTION_TAB);

const noop = (): void => {};

let onSuccessfulSaveCallback: () => void = noop;

strictInject(OPEN_CONFIGURATION_MODAL_BUS).registerHandler(openModal);
provide(CLOSE_CONFIGURATION_MODAL, closeModal);

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

    if (configuration_helper.is_success.value) {
        onSuccessfulSaveCallback();
    }
}

function switchTab(tab: ConfigurationTab): void {
    current_tab.value = tab;
    configuration_helper.resetSelection();
}
</script>

<style scoped lang="scss">
.artidoc-config-modal-with-fields > .tlp-modal-header {
    border-bottom: 0;
}
</style>
