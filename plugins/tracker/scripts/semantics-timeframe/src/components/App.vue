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
            />
        </form>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

import TimeframeBasedOnFieldsConfig from "./TimeframeBasedOnFieldsConfig.vue";
import TimeframeAdminSubmitButtons from "./TimeframeAdminSubmitButtons.vue";
import TimeframeImpliedFromAnotherTrackerConfig from "./TimeframeImpliedFromAnotherTrackerConfig.vue";
import TimeframeConfigModeSelector from "./TimeframeConfigModeSelector.vue";

import type { TrackerField, Tracker } from "../type";
import { MODE_BASED_ON_TRACKER_FIELDS } from "../constants";

@Component({
    components: {
        TimeframeBasedOnFieldsConfig,
        TimeframeAdminSubmitButtons,
        TimeframeImpliedFromAnotherTrackerConfig,
        TimeframeConfigModeSelector,
    },
})
export default class App extends Vue {
    @Prop({ required: true })
    readonly usable_date_fields!: TrackerField[];

    @Prop({ required: true })
    readonly usable_numeric_fields!: TrackerField[];

    @Prop({ required: true })
    readonly suitable_trackers!: Tracker[];

    @Prop({ required: true })
    readonly start_date_field_id!: number | "";

    @Prop({ required: true })
    readonly end_date_field_id!: number | "";

    @Prop({ required: true })
    readonly duration_field_id!: number | "";

    @Prop({ required: true })
    readonly implied_from_tracker_id!: number | "";

    @Prop({ required: true })
    readonly current_tracker_id!: number;

    @Prop({ required: true })
    readonly target_url!: string;

    @Prop({ required: true })
    readonly csrf_token!: string;

    @Prop({ required: true })
    readonly has_other_trackers_implying_their_timeframes!: boolean;

    @Prop({ required: true })
    readonly has_tracker_charts!: boolean;

    @Prop({ required: true })
    readonly has_artifact_link_field!: boolean;

    @Prop({ required: true })
    readonly semantic_presentation!: string;

    is_based_on_tracker_fields_mode_enabled = true;

    toggleTimeframeConfigs(value: string): void {
        this.is_based_on_tracker_fields_mode_enabled = value === MODE_BASED_ON_TRACKER_FIELDS;
    }
}
</script>
