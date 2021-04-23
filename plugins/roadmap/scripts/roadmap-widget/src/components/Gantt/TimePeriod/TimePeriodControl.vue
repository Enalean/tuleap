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
  -
  -->

<template>
    <div class="tlp-form-element roadmap-gantt-control">
        <label class="tlp-label roadmap-gantt-control-label" v-bind:for="id" v-translate>
            Timescale
        </label>
        <select
            class="tlp-select tlp-select-small tlp-select-adjusted"
            v-bind:id="id"
            v-on:change="$emit('input', $event.target.value)"
            data-test="select-timescale"
        >
            <option value="week" v-bind:selected="this.value === 'week'" v-translate>Week</option>
            <option value="month" v-bind:selected="this.value === 'month'" v-translate>
                Month
            </option>
            <option value="quarter" v-bind:selected="this.value === 'quarter'" v-translate>
                Quarter
            </option>
        </select>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { getUniqueId } from "../../../helpers/uniq-id-generator";
import type { TimeScale } from "../../../type";

@Component
export default class TimePeriodControl extends Vue {
    @Prop({ required: true })
    readonly value!: TimeScale;

    get id(): string {
        return getUniqueId("roadmap-gantt-timescale");
    }
}
</script>
