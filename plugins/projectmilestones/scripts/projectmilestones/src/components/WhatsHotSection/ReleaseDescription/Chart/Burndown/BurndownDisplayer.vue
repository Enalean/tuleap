<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
        <chart-error
            v-if="has_error_duration || has_error_start_date || is_under_calculation"
            v-bind:has_error_duration="has_error_duration"
            v-bind:message_error_duration="message_error_duration"
            v-bind:has_error_start_date="has_error_start_date"
            v-bind:message_error_start_date="message_error_start_date"
            v-bind:is_under_calculation="is_under_calculation"
            v-bind:message_error_under_calculation="
                $gettext('Burndown is under calculation. It will be available in a few minutes.')
            "
        />
        <burndown v-else v-bind:release_data="release_data" />
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import type { MilestoneData } from "../../../../../type";
import Vue from "vue";
import ChartError from "../ChartError.vue";
import Burndown from "./Burndown.vue";
import { State } from "vuex-class";
@Component({
    components: { ChartError, Burndown },
})
export default class BurndownDisplayer extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @State
    readonly is_timeframe_duration!: boolean;
    @State
    readonly label_start_date!: string;
    @State
    readonly label_timeframe!: string;

    get message_error_duration(): string {
        return this.$gettextInterpolate(
            this.$gettext("'%{field_name}' field is empty or invalid."),
            { field_name: this.label_timeframe }
        );
    }

    get message_error_start_date(): string {
        return this.$gettextInterpolate(
            this.$gettext("'%{field_name}' field is empty or invalid."),
            { field_name: this.label_start_date }
        );
    }

    get has_error_duration(): boolean {
        if (!this.is_timeframe_duration) {
            return !this.release_data.end_date;
        }

        if (!this.release_data.burndown_data) {
            return true;
        }

        return (
            this.release_data.burndown_data.duration === null ||
            this.release_data.burndown_data.duration === 0
        );
    }

    get has_error_start_date(): boolean {
        return !this.release_data.start_date;
    }

    get is_under_calculation(): boolean {
        if (!this.release_data.burndown_data) {
            return false;
        }

        return this.release_data.burndown_data.is_under_calculation;
    }
}
</script>
