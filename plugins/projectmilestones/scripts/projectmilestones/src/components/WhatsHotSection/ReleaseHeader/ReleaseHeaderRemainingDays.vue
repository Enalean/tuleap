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
    <div
        class="release-remaining tlp-tooltip tlp-tooltip-left"
        v-bind:data-tlp-tooltip="get_tooltip_effort_date"
    >
        <div class="release-remaining-header">
            <i class="release-remaining-icon fas fa-calendar-alt"></i>
            <span
                class="release-remaining-value"
                v-bind:class="{
                    'release-remaining-value-danger': date_close_to_end,
                    'release-remaining-value-success': are_dates_correctly_set,
                    'release-remaining-value-disabled': disabled_date,
                }"
                data-test="display-remaining-day-text"
            >
                {{ formatDate(release_data.number_days_until_end) }}
            </span>
            <span class="release-remaining-text">{{ days_to_go_label }}</span>
        </div>
        <div class="release-remaining-progress">
            <div
                class="release-remaining-progress-value"
                v-bind:class="{
                    'release-remaining-progress-value-danger': date_close_to_end,
                    'release-remaining-progress-value-success': are_dates_correctly_set,
                    'release-remaining-progress-value-disabled': disabled_date,
                }"
                v-bind:style="{ width: get_tooltip_effort_date }"
                data-test="display-remaining-day-value"
            ></div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { MilestoneData } from "../../../type";

@Component
export default class ReleaseHeaderRemainingDays extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;

    disabled_date =
        typeof this.release_data.number_days_since_start !== "number" ||
        typeof this.release_data.number_days_until_end !== "number";

    formatDate = (date: number | null): number => (date && date > 0 ? date : 0);

    get are_dates_correctly_set(): boolean {
        if (
            typeof this.release_data.number_days_since_start !== "number" ||
            !this.release_data.number_days_until_end
        ) {
            return false;
        }
        return (
            this.release_data.number_days_since_start >= 0 &&
            this.release_data.number_days_until_end > 0 &&
            this.dates_progress < 80
        );
    }

    get date_close_to_end(): boolean {
        return this.dates_progress >= 80 && this.dates_progress < 100;
    }

    get dates_progress(): number {
        const days_since_start = this.release_data.number_days_since_start;
        const days_until_end = this.release_data.number_days_until_end;

        if (typeof days_since_start !== "number" || typeof days_until_end !== "number") {
            return 0;
        }

        if (days_since_start < 0) {
            return 0;
        }

        if (days_since_start > 0 && days_until_end < 0) {
            return 100;
        }

        return (days_since_start / (days_since_start + days_until_end)) * 100;
    }

    get get_tooltip_effort_date(): string {
        const days_since_start = this.release_data.number_days_since_start;
        const days_until_end = this.release_data.number_days_until_end;

        if (typeof days_since_start !== "number") {
            return this.$gettext("No start date defined.");
        }

        if (typeof days_until_end !== "number") {
            return this.$gettext("No end date defined.");
        }

        return this.dates_progress.toFixed(2).toString() + "%";
    }

    get days_to_go_label(): string {
        const days_to_go = this.release_data.number_days_until_end ?? 0;
        return this.$ngettext("day to go", "days to go", days_to_go);
    }
}
</script>
