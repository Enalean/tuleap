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
    <div class="past-release closed-release-header-badge">
        <i class="release-remaining-icon fa fa-flag-checkered"></i>
        <span class="release-remaining-value" data-test="points-initial-value">
            {{ formatPoints(release_data.initial_effort) }}
        </span>
        <span class="release-remaining-text">{{ pts_done }}</span>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { MilestoneData } from "../../../type";

@Component
export default class PastReleaseHeaderInitialPoints extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;

    formatPoints = (pts: number | null): number => pts ?? 0;

    get pts_done(): string {
        const initial_effort = this.release_data.initial_effort ?? 0;
        return this.$ngettext("pt done", "pts done", initial_effort);
    }
}
</script>
