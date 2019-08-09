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
    <div class="project-release-header" v-on:click="$emit('toggleReleaseDetails')">
        <i class="project-release-whats-hot-icon fa"></i>
        <h1 class="project-release-title">
            <translate v-bind:translate-params="{ release_label: releaseData.label }">
                Release %{release_label}
            </translate>
        </h1>
        <span class="project-release-date" v-if="startDateExist()">
            {{ formatDate(releaseData.start_date) }}
            <i class="release-date-icon fa fa-long-arrow-right" data-test="display-arrow"></i>
            {{ formatDate(releaseData.end_date) }}
        </span>
        <div class="release-spacer"></div>
        <div class="release-remaining-effort-badges">
            <release-header-remaining-days data-test="display-remaining-days" v-bind:release-data="releaseData"/>
            <release-header-remaining-points data-test="display-remaining-points" v-bind:release-data="releaseData"/>
        </div>
    </div>
</template>

<script lang="ts">
import { formatDateYearMonthDay } from "../../../helpers/date-formatters";
import ReleaseHeaderRemainingDays from "./ReleaseHeaderRemainingDays.vue";
import ReleaseHeaderRemainingPoints from "./ReleaseHeaderRemainingPoints.vue";
import Vue from "vue";
import { MilestoneData } from "../../../type";
import { Component, Prop } from "vue-property-decorator";

@Component({
    components: { ReleaseHeaderRemainingPoints, ReleaseHeaderRemainingDays }
})
export default class ReleaseHeader extends Vue {
    @Prop()
    readonly releaseData!: MilestoneData;

    formatDate = (date: string): string => formatDateYearMonthDay(date);

    startDateExist(): boolean {
        return this.releaseData.start_date !== null;
    }
}
</script>
