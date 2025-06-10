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
        <tracker-selection-introductory-text v-bind:selected_tracker="new_selected_tracker" />
        <tracker-selection
            v-bind:allowed_trackers="configuration_helper.allowed_trackers"
            v-bind:selected_tracker="new_selected_tracker"
            v-bind:is_tracker_selection_disabled="configuration_helper.is_success.value"
            v-on:select-tracker="onSelectTracker"
        />
    </div>
    <configuration-modal-footer
        v-bind:current_tab="TRACKER_SELECTION_TAB"
        v-bind:configuration_helper="configuration_helper"
        v-bind:is_submit_button_disabled="configuration_helper.is_submit_button_disabled.value"
        v-bind:on_save_callback="configuration_helper.onSubmit"
    />
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Option } from "@tuleap/option";
import type { ConfigurationScreenHelper } from "@/composables/useConfigurationScreenHelper";
import { TRACKER_SELECTION_TAB } from "@/components/configuration/configuration-modal";
import TrackerSelectionIntroductoryText from "@/components/configuration/TrackerSelectionIntroductoryText.vue";
import TrackerSelection from "@/components/configuration/TrackerSelection.vue";
import ConfigurationModalFooter from "@/components/configuration/ConfigurationModalFooter.vue";
import type { Tracker } from "@/stores/configuration-store";

const props = defineProps<{
    configuration_helper: ConfigurationScreenHelper;
}>();

const new_selected_tracker = computed(() =>
    Option.fromNullable(props.configuration_helper.new_selected_tracker.value),
);

function onSelectTracker(tracker: Option<Tracker>): void {
    tracker.apply((new_tracker) => {
        // eslint-disable-next-line vue/no-mutating-props
        props.configuration_helper.new_selected_tracker.value = new_tracker;
    });
}
</script>
