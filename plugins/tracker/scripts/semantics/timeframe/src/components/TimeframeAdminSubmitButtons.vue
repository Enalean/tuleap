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
    <div class="tlp-pane-section-submit">
        <button class="tlp-button-primary" type="submit" name="update-semantic-timeframe">
            {{ $gettext("Save your modifications") }}
        </button>

        <template v-if="is_semantic_configured">
            <button
                class="tlp-button-danger tlp-button-outline reset-semantic-timeframe"
                type="submit"
                name="reset-semantic-timeframe"
                id="reset-semantic-timeframe"
                data-test="reset-button"
                v-bind:title="cannot_reset_message"
                v-bind:disabled="is_reset_disabled"
            >
                {{ $gettext("Reset this semantic") }}
            </button>
        </template>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

@Component
export default class TimeframeAdminSubmitButtons extends Vue {
    @Prop({ required: true })
    private readonly start_date_field_id!: number | "";

    @Prop({ required: true })
    private readonly end_date_field_id!: number | "";

    @Prop({ required: true })
    private readonly duration_field_id!: number | "";

    @Prop({ required: true })
    private readonly has_other_trackers_implying_their_timeframes!: boolean;

    @Prop({ required: true })
    private readonly has_tracker_charts!: boolean;

    @Prop({ required: true })
    private readonly implied_from_tracker_id!: number | "";

    get is_semantic_configured(): boolean {
        return (
            (this.start_date_field_id !== "" && this.end_date_field_id !== "") ||
            (this.start_date_field_id !== "" && this.duration_field_id !== "") ||
            this.implied_from_tracker_id !== ""
        );
    }

    get is_reset_disabled(): boolean {
        return this.has_other_trackers_implying_their_timeframes || this.has_tracker_charts;
    }

    get cannot_reset_message(): string {
        if (this.has_other_trackers_implying_their_timeframes) {
            return this.$gettext(
                "You cannot reset this semantic because some trackers inherit their own semantics timeframe from this one."
            );
        }

        if (this.has_tracker_charts) {
            return this.$gettext(
                "You cannot reset this semantic because this tracker has a burnup, burndown or another chart rendered by an external plugin"
            );
        }

        return "";
    }
}
</script>
