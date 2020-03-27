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
        <h1 class="project-release-title" data-test="title-release">
            {{ release_data.label }}
        </h1>
        <span class="project-release-date" v-if="startDateExist()">
            {{ formatDate(release_data.start_date) }}
            <i class="release-date-icon fa fa-long-arrow-right" data-test="display-arrow"></i>
            {{ formatDate(release_data.end_date) }}
        </span>
        <div class="release-spacer"></div>
        <div
            v-if="isLoading"
            class="tlp-skeleton-text release-remaining-disabled"
            data-test="display-skeleton"
        ></div>
        <div v-else-if="!isPastRelease" class="release-remaining-effort-badges">
            <release-header-remaining-days v-bind:release_data="release_data" />
            <release-header-remaining-points v-bind:release_data="release_data" />
        </div>
        <div v-else>
            <past-release-header-initial-points v-bind:release_data="release_data" />
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
import PastReleaseHeaderInitialPoints from "./PastReleaseHeaderInitialPoints.vue";

@Component({
    components: {
        PastReleaseHeaderInitialPoints,
        ReleaseHeaderRemainingPoints,
        ReleaseHeaderRemainingDays,
    },
})
export default class ReleaseHeader extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @Prop()
    readonly isLoading!: boolean;
    @Prop()
    readonly isPastRelease!: boolean;

    formatDate = (date: string): string => formatDateYearMonthDay(date);

    startDateExist(): boolean {
        return this.release_data.start_date !== null;
    }
}
</script>
