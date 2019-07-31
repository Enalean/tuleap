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
    <div class="release-remaining tlp-tooltip tlp-tooltip-left"
         v-bind:data-tlp-tooltip="get_tooltip_effort_date"
         data-test="display-remaining-days-tooltip"
    >
        <div class="release-remaining-header">
            <i class="release-remaining-icon fa fa-calendar"></i>
            <span class="release-remaining-value"
                  v-bind:class="{ 'release-remaining-value-danger': are_dates_correctly_set, 'release-remaining-value-disabled': disabled_date }"
                  data-test="display-remaining-day-text"
            >
                {{ formatDate(releaseData.number_days_until_end) }}
            </span>
            <translate class="release-remaining-text" v-bind:translate-n="releaseData.number_days_until_end"
                       translate-plural="days to go"
            >
                day to go
            </translate>
        </div>
        <div class="release-remaining-progress">
            <div class="release-remaining-progress-value"
                 v-bind:class="{ 'release-remaining-progress-value-danger': are_dates_correctly_set, 'release-remaining-progress-value-disabled': disabled_date }"
                 v-bind:style="{ width: get_tooltip_effort_date }"
                 data-test="display-remaining-day-value"
            >
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";

export default Vue.extend({
    name: "ReleaseHeaderRemainingDays",
    props: {
        releaseData: Object
    },
    data() {
        return {
            disabled_date:
                (!this.releaseData.number_days_since_start &&
                    this.releaseData.number_days_since_start !== 0) ||
                (!this.releaseData.number_days_until_end &&
                    this.releaseData.number_days_until_end !== 0)
        };
    },
    computed: {
        are_dates_correctly_set(): boolean {
            return (
                this.releaseData.number_days_since_start >= 0 &&
                this.releaseData.number_days_until_end > 0
            );
        },
        get_tooltip_effort_date(): string {
            const days_since_start = this.releaseData.number_days_since_start;
            const days_until_end = this.releaseData.number_days_until_end;

            if (!days_since_start && days_since_start !== 0) {
                return this.$gettext("No start date defined.");
            }

            if (!days_until_end && days_until_end !== 0) {
                return this.$gettext("No end date defined.");
            }

            if (days_since_start < 0) {
                return "0.00%";
            }

            if (days_since_start > 0 && days_until_end < 0) {
                return "100.00%";
            }

            return (
                (
                    (this.releaseData.number_days_since_start /
                        (this.releaseData.number_days_since_start +
                            this.releaseData.number_days_until_end)) *
                    100
                )
                    .toFixed(2)
                    .toString() + "%"
            );
        }
    },
    methods: {
        formatDate(date: number): number {
            return date && date > 0 ? date : 0;
        }
    }
});
</script>
