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
         v-bind:data-tlp-tooltip="get_tooltip_effort_points"
         data-test="display-remaining-points-tooltip"
    >
        <div class="release-remaining-header">
            <i class="release-remaining-icon fa fa-flag-checkered"></i>
            <span class="release-remaining-value"
                  v-bind:class="{ 'release-remaining-value-disabled': disabled_points, 'release-remaining-value-success': are_all_effort_defined}"
                  data-test="display-remaining-points-text"
            >
                {{ formatPoints(releaseData.remaining_effort) }}
            </span>
            <translate class="release-remaining-text" v-bind:translate-n="releaseData.remaining_effort"
                       translate-plural="pts to go"
            >
                pt to go
            </translate>
        </div>
        <div class="release-remaining-progress">
            <div class="release-remaining-progress-value"
                 v-bind:class="{ 'release-remaining-progress-value-success': are_all_effort_defined, 'release-remaining-progress-value-disabled': disabled_points }"
                 v-bind:style="{ width: get_tooltip_effort_points }"
                 data-test="display-remaining-points-value"
            >
            </div>
        </div>
    </div>
</template>

<script>
import { sprintf } from "sprintf-js";

export default {
    name: "ReleaseHeaderRemainingPoints",
    props: {
        releaseData: Object
    },
    data() {
        return {
            disabled_points:
                (!this.releaseData.remaining_effort && this.releaseData.remaining_effort !== 0) ||
                !this.releaseData.initial_effort ||
                this.releaseData.initial_effort < this.releaseData.remaining_effort
        };
    },
    computed: {
        are_all_effort_defined() {
            return (
                this.releaseData.remaining_effort > 0 &&
                this.releaseData.initial_effort > 0 &&
                this.releaseData.initial_effort > this.releaseData.remaining_effort
            );
        },
        get_tooltip_effort_points() {
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

            if (initial_effort < remaining_effort) {
                return sprintf(
                    this.$gettext(
                        "Initial effort (%s) should be bigger or equal to remaining effort (%s)."
                    ),
                    initial_effort,
                    remaining_effort
                );
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
        formatPoints(pts) {
            return pts ? pts : 0;
        }
    }
};
</script>
