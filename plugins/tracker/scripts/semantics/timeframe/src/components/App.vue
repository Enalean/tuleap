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
        <p v-translate>The timeframe of an artifact will be defined by:</p>

        <form v-bind:action="target_url" method="POST">
            <input type="hidden" name="challenge" v-bind:value="csrf_token" />
            <timeframe-based-on-fields-config
                v-bind:usable_date_fields="usable_date_fields"
                v-bind:usable_numeric_fields="usable_numeric_fields"
                v-bind:selected_start_date_field_id="start_date_field_id"
                v-bind:selected_end_date_field_id="end_date_field_id"
                v-bind:selected_duration_field_id="duration_field_id"
            />

            <timeframe-admin-submit-buttons
                v-bind:start_date_field_id="start_date_field_id"
                v-bind:end_date_field_id="end_date_field_id"
                v-bind:duration_field_id="duration_field_id"
                v-bind:has_other_trackers_implying_their_timeframes="
                    has_other_trackers_implying_their_timeframes
                "
                v-bind:has_tracker_charts="has_tracker_charts"
            />
        </form>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

import TimeframeBasedOnFieldsConfig from "./TimeframeBasedOnFieldsConfig.vue";
import TimeframeAdminSubmitButtons from "./TimeframeAdminSubmitButtons.vue";

import type { TrackerField } from "../../type";

@Component({
    components: {
        TimeframeBasedOnFieldsConfig,
        TimeframeAdminSubmitButtons,
    },
})
export default class App extends Vue {
    @Prop({ required: true })
    private readonly usable_date_fields!: TrackerField[];

    @Prop({ required: true })
    private readonly usable_numeric_fields!: TrackerField[];

    @Prop({ required: true })
    private readonly start_date_field_id!: number | "";

    @Prop({ required: true })
    private readonly end_date_field_id!: number | "";

    @Prop({ required: true })
    private readonly duration_field_id!: number | "";

    @Prop({ required: true })
    private readonly target_url!: string;

    @Prop({ required: true })
    private readonly csrf_token!: string;

    @Prop({ required: true })
    private readonly has_other_trackers_implying_their_timeframes!: boolean;

    @Prop({ required: true })
    private readonly has_tracker_charts!: boolean;
}
</script>
