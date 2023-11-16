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
    <div class="container-chart-burndown-burnup">
        <div v-if="is_loading" class="release-loader" data-test="loading-data"></div>
        <div v-else-if="!has_rest_error" class="release-charts-row">
            <div
                v-if="burndown_exists"
                class="release-chart-displayer release-chart-displayer-burndown"
            >
                <h2 class="tlp-pane-subtitle">{{ burndown_label }}</h2>
                <burndown-displayer
                    v-bind:release_data="release_data"
                    v-bind:burndown_data="burndown_data"
                />
            </div>
            <div
                v-if="burnup_exists"
                data-test="burnup-exists"
                class="release-chart-displayer release-chart-displayer-burnup"
            >
                <h2 class="tlp-pane-subtitle project-milestones-chart-label">{{ burnup_label }}</h2>
                <burnup-displayer
                    v-bind:release_data="release_data"
                    v-bind:burnup_data="burnup_data"
                />
            </div>
        </div>
        <div v-if="has_rest_error" class="tlp-alert-danger" data-test="error-rest">
            {{ message_error_rest }}
        </div>
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import type { BurndownData, BurnupData, MilestoneData } from "../../../../type";
import Vue from "vue";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
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
    burndown_data: BurndownData | null = null;
    burnup_data: BurnupData | null = null;

    get burndown_exists(): boolean {
        if (this.burndown_data !== null) {
            this.$emit("burndown-exists");
            return true;
        }
        return false;
    }

    get burnup_exists(): boolean {
        if (this.burnup_data !== null) {
            this.$emit("burnup-exists");
            return true;
        }
        return false;
    }

    get burndown_label(): string {
        if (this.burndown_data && this.burndown_data.label) {
            return this.burndown_data.label;
        }

        return this.$gettext("Burndown");
    }

    get burnup_label(): string {
        if (this.burnup_data && this.burnup_data.label) {
            return this.burnup_data.label;
        }

        return this.$gettext("Burnup");
    }

    async mounted(): Promise<void> {
        try {
            const burndown_values = await getChartData(this.release_data.id);
            this.burndown_data = getBurndownDataFromType(burndown_values);
            this.burnup_data = getBurnupDataFromType(burndown_values);
        } catch (rest_error) {
            this.has_rest_error = true;
            await this.handle_error(rest_error);
        } finally {
            this.is_loading = false;
        }
    }

    async handle_error(rest_error: unknown): Promise<void> {
        try {
            if (!(rest_error instanceof FetchWrapperError)) {
                return;
            }
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
