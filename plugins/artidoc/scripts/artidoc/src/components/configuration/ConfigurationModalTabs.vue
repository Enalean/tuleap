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
    <nav class="tlp-tabs">
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
            v-bind:disabled="is_fields_selection_button_disabled"
            v-bind:class="getFieldsTabClasses()"
            v-bind:data-tlp-tooltip="tooltip_message"
            v-on:click="switchToTab(READONLY_FIELDS_SELECTION_TAB)"
            data-test="fields-selection-tab"
        >
            {{ $gettext("Fields selection") }}
        </button>
        <button
            class="tlp-tab"
            type="button"
            v-if="can_user_display_versions"
            v-bind:class="{ 'tlp-tab-active': current_tab === EXPERIMENTAL_FEATURES_TAB }"
            v-on:click="switchToTab(EXPERIMENTAL_FEATURES_TAB)"
            data-test="experimental-features-tab"
        >
            {{ $gettext("Experimental features") }}
        </button>
    </nav>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import type { ConfigurationTab } from "@/components/configuration/configuration-modal";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import {
    READONLY_FIELDS_SELECTION_TAB,
    TRACKER_SELECTION_TAB,
    EXPERIMENTAL_FEATURES_TAB,
} from "@/components/configuration/configuration-modal";
import { CAN_USER_DISPLAY_VERSIONS } from "@/can-user-display-versions-injection-key";

const states_collection = strictInject(SECTIONS_STATES_COLLECTION);
const selected_tracker = strictInject(SELECTED_TRACKER);
const can_user_display_versions = strictInject(CAN_USER_DISPLAY_VERSIONS);

const { $gettext } = useGettext();

const props = defineProps<{
    current_tab: ConfigurationTab;
}>();

const emit = defineEmits<{
    (e: "switch-configuration-tab", value: ConfigurationTab): void;
}>();

const is_fields_selection_button_disabled = computed(
    () =>
        selected_tracker.value.isNothing() ||
        states_collection.has_at_least_one_section_in_edit_mode.value,
);

const tooltip_message = computed(() => {
    if (selected_tracker.value.isNothing()) {
        return $gettext("Please select a tracker first");
    }

    if (states_collection.has_at_least_one_section_in_edit_mode.value) {
        return $gettext("The document is being edited. Please save your work beforehand.");
    }

    return "";
});

const getFieldsTabClasses = (): string[] => {
    return [
        is_fields_selection_button_disabled.value ? "tlp-tooltip tlp-tooltip-right" : "",
        props.current_tab === READONLY_FIELDS_SELECTION_TAB ? "tlp-tab-active" : "",
    ];
};

const switchToTab = (tab: ConfigurationTab): void => {
    emit("switch-configuration-tab", tab);
};
</script>

<style scoped lang="scss">
.tlp-tabs {
    margin: 0;
}

.tlp-tooltip::before {
    text-transform: none;
}
</style>
