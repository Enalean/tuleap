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
    <div class="release-remaining-badges">
        <div class="release-remaining tlp-tooltip tlp-tooltip-bottom"
             v-bind:data-tlp-tooltip="getTooltipEffortDate"
             data-test="display-remaining-days-tooltip"
        >
            <div class="release-remaining-header">
                <i class="release-remaining-icon fa fa-calendar"></i>
                <span class="release-remaining-value"
                      v-bind:class="{ 'release-remaining-value-danger': areDatesCorrectlySet && !disabledDate, 'release-remaining-value-disabled': disabledDate }"
                      data-test="display-remaining-day-text">
                    {{ formatDate(releaseData.number_days_until_end) }}
                </span>
                <translate class="release-remaining-text" v-bind:translate-n="releaseData.number_days_until_end"
                           translate-plural="days to go"
                >
                    day to go
                </translate>
            </div>
        </div>
        <div class="release-remaining tlp-tooltip tlp-tooltip-bottom"
             v-bind:data-tlp-tooltip="getTooltipEffortPoints"
             data-test="display-remaining-points-tooltip"
        >
            <div class="release-remaining-header">
                <i class="release-remaining-icon fa fa-flag-checkered"></i>
                <span class="release-remaining-value"
                      v-bind:class="{ 'release-remaining-value-disabled': disabledPoints, 'release-remaining-value-success': areAllEffortDefined && !disabledPoints}"
                      data-test="display-remaining-points-text">{{ formatPoints(releaseData.remaining_effort) }}</span>
                <translate class="release-remaining-text" v-bind:translate-n="releaseData.remaining_effort"
                           translate-plural="pts to go"
                >
                    pt to go
                </translate>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ReleaseHeaderRemainingEffort",
    props: {
        releaseData: Object
    },
    data() {
        return {
            disabledDate:
                (!this.releaseData.number_days_since_start &&
                    this.releaseData.number_days_since_start !== 0) ||
                (!this.releaseData.number_days_until_end &&
                    this.releaseData.number_days_until_end !== 0),
            disabledPoints:
                (!this.releaseData.remaining_effort && this.releaseData.remaining_effort !== 0) ||
                !this.releaseData.initial_effort
        };
    },
    computed: {
        areDatesCorrectlySet() {
            return (
                this.releaseData.number_days_since_start >= 0 &&
                this.releaseData.number_days_until_end > 0
            );
        },
        areAllEffortDefined() {
            return this.releaseData.remaining_effort > 0 && this.releaseData.initial_effort > 0;
        },
        getTooltipEffortDate() {
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
        },
        getTooltipEffortPoints() {
            const remaining_effort = this.releaseData.remaining_effort;
            const initial_effort = this.releaseData.initial_effort;

            if (!remaining_effort && remaining_effort !== 0) {
                return this.$gettext("No remaining effort defined.");
            }

            if (!initial_effort && initial_effort !== 0) {
                return this.$gettext("No initial effort defined.");
            }

            if (initial_effort === 0) {
                return this.$gettext("Initial effort equal at 0.");
            }

            return (
                (
                    ((this.releaseData.initial_effort - this.releaseData.remaining_effort) /
                        this.releaseData.initial_effort) *
                    100
                )
                    .toFixed(2)
                    .toString() + "%"
            );
        }
    },
    methods: {
        formatDate(date) {
            return date && date > 0 ? date : 0;
        },
        formatPoints(pts) {
            return pts ? pts : 0;
        }
    }
};
</script>
