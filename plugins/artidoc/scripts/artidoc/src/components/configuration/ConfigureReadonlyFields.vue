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
            v-bind:selected_fields="new_selected_fields"
            v-bind:available_fields="new_available_fields"
        />
    </div>

    <configure-readonly-fields-footer
        v-bind:is_submit_button_disabled="is_submit_button_disabled"
        v-bind:new_selected_fields="new_selected_fields"
        v-bind:configuration_saver="configuration_saver"
    />
</template>

<script setup lang="ts">
import { ref, toRaw, watch } from "vue";
import FieldsSelectionIntroductoryText from "@/components/configuration/FieldsSelectionIntroductoryText.vue";
import FieldsSelection from "@/components/configuration/FieldsSelection.vue";
import ConfigureReadonlyFieldsFooter from "@/components/configuration/ConfigureReadonlyFieldsFooter.vue";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SELECTED_FIELDS } from "@/configuration/SelectedFieldsCollection";
import { AVAILABLE_FIELDS } from "@/configuration/AvailableFieldsCollection";
import { buildFieldsConfigurationSaver } from "@/configuration/FieldsConfigurationSaver";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";

const document_id = strictInject(DOCUMENT_ID);
const selected_tracker = strictInject(SELECTED_TRACKER);
const selected_fields = strictInject(SELECTED_FIELDS);
const available_fields = strictInject(AVAILABLE_FIELDS);

const new_selected_fields = ref<ConfigurationField[]>(
    structuredClone(toRaw(selected_fields.value)),
);
const new_available_fields = ref<ConfigurationField[]>(
    structuredClone(toRaw(available_fields.value)),
);

const configuration_saver = buildFieldsConfigurationSaver(
    document_id,
    selected_tracker,
    selected_fields,
    available_fields,
);

const is_submit_button_disabled = ref(true);
watch(new_selected_fields.value, () => {
    is_submit_button_disabled.value =
        JSON.stringify(toRaw(new_selected_fields.value)) ===
        JSON.stringify(toRaw(selected_fields.value));
});
</script>
