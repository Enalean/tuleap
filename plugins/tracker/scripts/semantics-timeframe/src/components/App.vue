<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div>
        <p>{{ semantic_presentation }}</p>
        <timeframe-config-mode-selector
            v-on:timeframe-mode-selected="toggleTimeframeConfigs"
            v-bind:implied_from_tracker_id="implied_from_tracker_id"
        />

        <form v-bind:action="target_url" method="POST">
            <input type="hidden" name="challenge" v-bind:value="csrf_token" />

            <timeframe-based-on-fields-config
                v-if="is_based_on_tracker_fields_mode_enabled"
                v-bind:usable_date_fields="usable_date_fields"
                v-bind:usable_numeric_fields="usable_numeric_fields"
                v-bind:selected_start_date_field_id="start_date_field_id"
                v-bind:selected_end_date_field_id="end_date_field_id"
                v-bind:selected_duration_field_id="duration_field_id"
            />

            <timeframe-implied-from-another-tracker-config
                v-else
                v-bind:suitable_trackers="suitable_trackers"
                v-bind:has_artifact_link_field="has_artifact_link_field"
                v-bind:implied_from_tracker_id="implied_from_tracker_id"
                v-bind:has_other_trackers_implying_their_timeframes="
                    has_other_trackers_implying_their_timeframes
                "
                v-bind:current_tracker_id="current_tracker_id"
            />

            <timeframe-admin-submit-buttons
                v-bind:start_date_field_id="start_date_field_id"
                v-bind:end_date_field_id="end_date_field_id"
                v-bind:duration_field_id="duration_field_id"
                v-bind:has_other_trackers_implying_their_timeframes="
                    has_other_trackers_implying_their_timeframes
                "
                v-bind:has_tracker_charts="has_tracker_charts"
                v-bind:implied_from_tracker_id="implied_from_tracker_id"
                v-bind:should_send_event_in_notification="should_send_event_in_notification"
            />
        </form>
    </div>
</template>

<script setup lang="ts">
import type { TrackerField, Tracker } from "../type";
import { MODE_BASED_ON_TRACKER_FIELDS } from "../constants";
import { ref } from "vue";
import TimeframeConfigModeSelector from "./TimeframeConfigModeSelector.vue";
import TimeframeBasedOnFieldsConfig from "./TimeframeBasedOnFieldsConfig.vue";
import TimeframeImpliedFromAnotherTrackerConfig from "./TimeframeImpliedFromAnotherTrackerConfig.vue";
import TimeframeAdminSubmitButtons from "./TimeframeAdminSubmitButtons.vue";

defineProps<{
    usable_date_fields: TrackerField[];
    usable_numeric_fields: TrackerField[];
    suitable_trackers: Tracker[];
    start_date_field_id: number | "";
    end_date_field_id: number | "";
    duration_field_id: number | "";
    implied_from_tracker_id: number | "";
    current_tracker_id: number;
    target_url: string;
    csrf_token: string;
    has_other_trackers_implying_their_timeframes: boolean;
    has_tracker_charts: boolean;
    has_artifact_link_field: boolean;
    semantic_presentation: string;
    should_send_event_in_notification: boolean;
}>();

const is_based_on_tracker_fields_mode_enabled = ref(true);

function toggleTimeframeConfigs(value: string): void {
    is_based_on_tracker_fields_mode_enabled.value = value === MODE_BASED_ON_TRACKER_FIELDS;
}
</script>
