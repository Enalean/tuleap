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
        v-bind:data-tlp-tooltip="get_tooltip_effort_points"
    >
        <div class="release-remaining-header">
            <i class="release-remaining-icon fa fa-flag-checkered"></i>
            <span
                class="release-remaining-value"
                v-bind:class="{
                    'release-remaining-value-disabled': disabled_points,
                    'release-remaining-value-success': are_all_effort_defined,
                }"
                data-test="points-remaining-value"
            >
                {{ formatPoints(release_data.remaining_effort) }}
            </span>
            <translate
                class="release-remaining-text"
                v-bind:translate-n="release_data.remaining_effort"
                translate-plural="pts to go"
            >
                pt to go
            </translate>
        </div>
        <div class="release-remaining-progress">
            <div
                class="release-remaining-progress-value"
                v-bind:class="{
                    'release-remaining-progress-value-success': are_all_effort_defined,
                    'release-remaining-progress-value-disabled': disabled_points,
                }"
                v-bind:style="{ width: get_tooltip_effort_points }"
                data-test="points-progress-value"
            ></div>
        </div>
    </div>
</template>

<script lang="ts">
import { sprintf } from "sprintf-js";
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { MilestoneData } from "../../../type";

@Component
export default class ReleaseHeaderRemainingPoints extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;

    disabled_points =
        typeof this.release_data.remaining_effort !== "number" ||
        !this.release_data.initial_effort ||
        this.release_data.initial_effort < this.release_data.remaining_effort;

    formatPoints = (pts: number | null): number => pts ?? 0;

    get are_all_effort_defined(): boolean {
        if (
            typeof this.release_data.remaining_effort !== "number" ||
            typeof this.release_data.initial_effort !== "number"
        ) {
            return false;
        }
        return (
            this.release_data.remaining_effort > 0 &&
            this.release_data.initial_effort > 0 &&
            this.release_data.initial_effort >= this.release_data.remaining_effort
        );
    }

    get get_tooltip_effort_points(): string {
        const remaining_effort = this.release_data.remaining_effort;
        const initial_effort = this.release_data.initial_effort;

        if (typeof remaining_effort !== "number") {
            return this.$gettext("No remaining effort defined.");
        }

        if (typeof initial_effort !== "number") {
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
            (((initial_effort - remaining_effort) / initial_effort) * 100).toFixed(2).toString() +
            "%"
        );
    }
}
</script>
