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
        <fields-selection-introductory-text v-bind:tracker="selected_tracker" />
        <fields-selection
            v-bind:selected_fields="selected_fields"
            v-bind:available_fields="available_fields"
        />
    </div>

    <configuration-modal-footer
        v-bind:current_tab="READONLY_FIELDS_SELECTION_TAB"
        v-bind:configuration_helper="configuration_helper"
        v-bind:on_save_callback="noop"
    />
</template>

<script setup lang="ts">
import type { Tracker } from "@/stores/configuration-store";
import FieldsSelectionIntroductoryText from "@/components/configuration/FieldsSelectionIntroductoryText.vue";
import FieldsSelection from "@/components/configuration/FieldsSelection.vue";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import ConfigurationModalFooter from "@/components/configuration/ConfigurationModalFooter.vue";
import { READONLY_FIELDS_SELECTION_TAB } from "@/components/configuration/configuration-modal";
import { noop } from "@/helpers/noop";
import type { ConfigurationScreenHelper } from "@/composables/useConfigurationScreenHelper";

defineProps<{
    configuration_helper: ConfigurationScreenHelper;
    selected_tracker: Tracker;
    selected_fields: ConfigurationField[];
    available_fields: ConfigurationField[];
}>();
</script>
