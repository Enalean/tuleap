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
        <div v-if="is_loading" class="release-loader" data-test="loading-data"></div>
        <div v-else-if="!has_rest_error">
            <div v-if="burndown_exists">
                <h2 class="tlp-pane-subtitle">{{ burndown_label }}</h2>
                <burndown-displayer v-bind:release_data="release_data" />
            </div>
            <div v-if="burnup_exists" data-test="burnup-exists">
                <h2 class="tlp-pane-subtitle project-milestones-chart-label">{{ burnup_label }}</h2>
                <burnup-displayer v-bind:release_data="release_data" />
            </div>
        </div>
        <div v-if="has_rest_error" class="tlp-alert-danger" data-test="error-rest">
            {{ message_error_rest }}
        </div>
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../../../type";
import Vue from "vue";
import { FetchWrapperError } from "tlp";
import { getChartData } from "../../../../api/rest-querier";
import { getBurndownDataFromType, getBurnupDataFromType } from "../../../../helpers/chart-helper";
import BurndownDisplayer from "./Burndown/BurndownDisplayer.vue";
import BurnupDisplayer from "./Burnup/BurnupDisplayer.vue";

@Component({
    components: { BurnupDisplayer, BurndownDisplayer },
})
export default class ChartDisplayer extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;

    is_loading = true;
    message_error_rest: string | null = null;
    has_rest_error = false;

    get burndown_exists(): boolean {
        return this.release_data.resources.burndown !== null;
    }

    get burnup_exists(): boolean {
        return this.release_data.burnup_data !== null;
    }

    get burndown_label(): string {
        if (this.release_data.burndown_data && this.release_data.burndown_data.label) {
            return this.release_data.burndown_data.label;
        }

        return this.$gettext("Burndown");
    }

    get burnup_label(): string {
        if (this.release_data.burnup_data && this.release_data.burnup_data.label) {
            return this.release_data.burnup_data.label;
        }

        return this.$gettext("Burnup");
    }

    async created(): Promise<void> {
        if (
            this.is_loading &&
            (!this.release_data.burndown_data || !this.release_data.burnup_data)
        ) {
            try {
                const burndown_values = await getChartData(this.release_data.id);
                this.release_data.burndown_data = getBurndownDataFromType(burndown_values);
                this.release_data.burnup_data = getBurnupDataFromType(burndown_values);
            } catch (rest_error) {
                this.has_rest_error = true;
                await this.handle_error(rest_error);
            } finally {
                this.is_loading = false;
            }
        } else {
            this.is_loading = false;
        }
    }

    async handle_error(rest_error: FetchWrapperError): Promise<void> {
        try {
            const { error } = await rest_error.response.json();
            this.message_error_rest = error.code + " " + error.message;
        } catch (error) {
            this.message_error_rest = this.$gettext("Oops, an error occurred!");
        } finally {
            this.is_loading = false;
        }
    }
}
</script>
