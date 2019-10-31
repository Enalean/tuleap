<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
        <div v-if="is_loading" class="release-loader" data-test="loading-data"></div>
        <burndown-chart-error
            v-else-if="has_error_rest || has_error_duration || has_error_start_date || is_under_calculation"
            v-bind:has_error_rest="has_error_rest"
            v-bind:message_error_rest="message_error_rest"
            v-bind:has_error_duration="has_error_duration"
            v-bind:message_error_duration="$gettext('\'duration\' field is empty or invalid.')"
            v-bind:has_error_start_date="has_error_start_date"
            v-bind:message_error_start_date="$gettext('\'start date\' field is empty or invalid.')"
            v-bind:is_under_calculation="is_under_calculation"
            v-bind:message_error_under_calculation="$gettext('Burndown is under calculation. It will be available in a few minutes.')"
        />
        <burndown-chart-displayer v-else v-bind:release_data="release_data"/>
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../../../type";
import Vue from "vue";
import BurndownChartError from "./BurndownChartError.vue";
import BurndownChartDisplayer from "./BurndownChartDisplayer.vue";
import { FetchWrapperError } from "tlp";
import { getBurndownData } from "../../../../api/rest-querier";
@Component({
    components: { BurndownChartError, BurndownChartDisplayer }
})
export default class BurndownChart extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;

    is_loading = true;
    message_error_rest: string | null = null;

    get has_error_rest(): boolean {
        return this.message_error_rest !== null;
    }

    async created(): Promise<void> {
        if (!this.release_data.burndown_data) {
            try {
                this.release_data.burndown_data = await getBurndownData(
                    this.release_data.id,
                    this.$store.state
                );
            } catch (rest_error) {
                await this.handle_error(rest_error);
            } finally {
                this.is_loading = false;
            }
        } else {
            this.is_loading = false;
        }
    }

    get has_error_duration(): boolean {
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

    async handle_error(rest_error: FetchWrapperError): Promise<void> {
        if (rest_error.response === undefined) {
            this.message_error_rest = this.$gettext("Oops, an error occurred!");
            throw rest_error;
        }
        try {
            const { error } = await rest_error.response.json();
            this.message_error_rest = error.code + " " + error.message;
        } catch (error) {
            this.message_error_rest = this.$gettext("Oops, an error occurred!");
        }
    }
}
</script>
