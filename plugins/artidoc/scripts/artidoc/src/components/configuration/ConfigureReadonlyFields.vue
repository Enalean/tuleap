<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <div class="tlp-modal-body">
        <fields-selection-introductory-text />
        <fields-selection
            v-bind:selected_fields="selected_fields"
            v-bind:available_fields="available_fields"
        />
    </div>

    <configuration-modal-footer
        v-bind:current_tab="READONLY_FIELDS_SELECTION_TAB"
        v-bind:on_save_callback="onFieldsSubmit"
        v-bind:is_submit_button_disabled="is_submit_button_disabled"
    />
</template>

<script setup lang="ts">
import { ref, toRaw, watch } from "vue";
import type { ConfigurationStore } from "@/stores/configuration-store";
import FieldsSelectionIntroductoryText from "@/components/configuration/FieldsSelectionIntroductoryText.vue";
import FieldsSelection from "@/components/configuration/FieldsSelection.vue";
import ConfigurationModalFooter from "@/components/configuration/ConfigurationModalFooter.vue";
import { READONLY_FIELDS_SELECTION_TAB } from "@/components/configuration/configuration-modal";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";

const props = defineProps<{
    configuration_store: ConfigurationStore;
}>();

const selected_fields = ref<ConfigurationField[]>(
    structuredClone(toRaw(props.configuration_store.selected_fields.value)),
);
const available_fields = ref<ConfigurationField[]>(
    structuredClone(toRaw(props.configuration_store.available_fields.value)),
);

function onFieldsSubmit(): void {
    props.configuration_store.saveFieldsConfiguration(selected_fields.value);
}

const is_submit_button_disabled = ref(true);
watch(selected_fields.value, () => {
    is_submit_button_disabled.value =
        JSON.stringify(toRaw(selected_fields.value)) ===
        JSON.stringify(toRaw(props.configuration_store.selected_fields.value));
});
</script>
