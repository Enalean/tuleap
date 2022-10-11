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
    <div class="tlp-form-element">
        <label class="tlp-label" for="imeframe-mode">
            {{ $gettext("The timeframe of an artifact will be") }}
        </label>
        <select
            id="timeframe-mode"
            name="timeframe-mode"
            class="tlp-form-element tlp-select tlp-select-adjusted"
            v-on:change="dispatchSelection"
            v-model="active_timeframe_mode"
            data-test="timeframe-mode-select-box"
            required
        >
            <option value="" disabled v-translate>Choose a method...</option>
            <option v-for="mode in timeframe_modes" v-bind:value="mode.id" v-bind:key="mode.id">
                {{ mode.name }}
            </option>
        </select>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MODE_BASED_ON_TRACKER_FIELDS, MODE_IMPLIED_FROM_ANOTHER_TRACKER } from "../../constants";
import type { TimeframeMode } from "../../type";

@Component
export default class TimeframeConfigModeSelector extends Vue {
    @Prop({ required: true })
    private readonly implied_from_tracker_id!: number | "";

    private readonly EVENT_NAME = "timeframe-mode-selected";

    mounted(): void {
        this.active_timeframe_mode =
            this.implied_from_tracker_id !== ""
                ? MODE_IMPLIED_FROM_ANOTHER_TRACKER
                : MODE_BASED_ON_TRACKER_FIELDS;

        this.dispatchSelection();
    }

    private active_timeframe_mode = "";

    dispatchSelection() {
        this.$emit(this.EVENT_NAME, this.active_timeframe_mode);
    }

    get timeframe_modes(): TimeframeMode[] {
        return [
            {
                id: MODE_BASED_ON_TRACKER_FIELDS,
                name: this.$gettext("Based on tracker fields"),
            },
            {
                id: MODE_IMPLIED_FROM_ANOTHER_TRACKER,
                name: this.$gettext("Inherited from another tracker"),
            },
        ];
    }
}
</script>
