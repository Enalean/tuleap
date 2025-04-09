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
    <nav class="tlp-tabs" v-if="are_fields_enabled">
        <button
            class="tlp-tab"
            type="button"
            v-bind:class="{ 'tlp-tab-active': current_tab === TRACKER_SELECTION_TAB }"
            v-on:click="switchToTab(TRACKER_SELECTION_TAB)"
            data-test="tracker-selection-tab"
        >
            {{ $gettext("Tracker selection") }}
        </button>
        <button
            class="tlp-tab"
            type="button"
            v-bind:disabled="!is_tracker_configured"
            v-bind:class="getFieldsTabClasses()"
            v-bind:data-tlp-tooltip="$gettext('Please select a tracker first')"
            v-on:click="switchToTab(READONLY_FIELDS_SELECTION_TAB)"
            data-test="fields-selection-tab"
        >
            {{ $gettext("Fields selection") }}
        </button>
    </nav>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { ARE_FIELDS_ENABLED } from "@/are-fields-enabled";
import type { Tracker } from "@/stores/configuration-store";
import type { ConfigurationTab } from "@/components/configuration/configuration-modal";
import {
    READONLY_FIELDS_SELECTION_TAB,
    TRACKER_SELECTION_TAB,
} from "@/components/configuration/configuration-modal";

const are_fields_enabled = strictInject(ARE_FIELDS_ENABLED);

const props = defineProps<{
    current_tab: ConfigurationTab;
    selected_tracker: Tracker | null;
}>();

const emit = defineEmits<{
    (e: "switch-configuration-tab", value: ConfigurationTab): void;
}>();

const is_tracker_configured = computed(() => {
    return props.selected_tracker !== null;
});

const getFieldsTabClasses = (): string[] => {
    return [
        is_tracker_configured.value ? "" : "tlp-tooltip tlp-tooltip-right",
        props.current_tab === READONLY_FIELDS_SELECTION_TAB ? "tlp-tab-active" : "",
    ];
};

const switchToTab = (tab: ConfigurationTab): void => {
    emit("switch-configuration-tab", tab);
};
</script>

<style scoped lang="scss">
.tlp-tooltip::before {
    text-transform: none;
}
</style>
