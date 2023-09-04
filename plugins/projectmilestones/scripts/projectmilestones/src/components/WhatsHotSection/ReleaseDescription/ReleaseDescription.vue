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
    <div class="release-content-description" v-bind:class="classes">
        <release-description-badges-tracker
            v-if="display_badges_tracker"
            v-bind:release_data="release_data"
        />
        <div v-if="release_data.post_processed_description" class="release-description-row">
            <div
                class="release-description"
                v-dompurify-html="release_data.post_processed_description"
            ></div>
        </div>
        <chart-displayer
            v-bind:class="{ 'only-one-chart': is_only_burndown || is_only_burnup }"
            v-bind:release_data="release_data"
            v-on:burndown-exists="burndown_exists"
            v-on:burnup-exists="burnup_exists"
        />
        <test-management-displayer
            v-if="is_testmanagement_available"
            v-bind:release_data="release_data"
            v-on:ttm-exists="ttm_chart_exists"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { MilestoneData } from "../../../type";
import ReleaseDescriptionBadgesTracker from "./ReleaseDescriptionBadgesTracker.vue";
import ChartDisplayer from "./Chart/ChartDisplayer.vue";
import TestManagementDisplayer from "./TestManagement/TestManagementDisplayer.vue";
import { is_testplan_activated } from "../../../helpers/test-management-helper";

@Component({
    components: {
        TestManagementDisplayer,
        ChartDisplayer,
        ReleaseDescriptionBadgesTracker,
    },
})
export default class ReleaseDescription extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;

    is_burndown = false;
    is_burnup = false;
    is_ttm = false;

    get is_testmanagement_available(): boolean {
        return is_testplan_activated(this.release_data);
    }

    get display_badges_tracker(): boolean {
        const trackers_to_display = this.release_data.number_of_artifact_by_trackers.filter(
            (tracker) => tracker.total_artifact > 0,
        );
        return trackers_to_display.length > 0;
    }

    get display_badges_on_line(): boolean {
        if (this.is_description) {
            return false;
        }

        return this.are_only_trackers || this.is_only_one_chart;
    }

    burndown_exists(): void {
        this.is_burndown = true;
    }

    burnup_exists(): void {
        this.is_burnup = true;
    }

    ttm_chart_exists(): void {
        this.is_ttm = true;
    }

    get is_description(): boolean {
        return (
            this.release_data.post_processed_description !== null &&
            this.release_data.post_processed_description !== ""
        );
    }

    get are_only_trackers(): boolean {
        return !this.is_description && !this.is_burnup && !this.is_burndown && !this.is_ttm;
    }

    get is_only_one_chart(): boolean {
        return this.is_only_burndown || this.is_only_burnup || this.is_only_ttm;
    }

    get is_only_burndown(): boolean {
        return this.is_burndown && !this.is_burnup && !this.is_ttm;
    }

    get is_only_burnup(): boolean {
        return !this.is_burndown && this.is_burnup && !this.is_ttm;
    }

    get is_only_ttm(): boolean {
        return !this.is_burndown && !this.is_burnup && this.is_ttm;
    }

    get classes(): string[] {
        const classes: string[] = [];

        if (this.display_badges_on_line) {
            classes.push("project-milestones-display-on-line-tracker");
        }

        if (this.are_only_trackers) {
            classes.push("project-milestones-only-trackers-to-display");
        }

        if (this.is_description) {
            classes.push("project-milestones-display-description");
        }

        if (this.is_ttm) {
            classes.push("project-milestones-display-ttm-chart");
        }

        if (this.is_burndown) {
            classes.push("project-milestones-display-burndown-chart");
        }

        if (this.is_burnup) {
            classes.push("project-milestones-display-burnup-chart");
        }

        if (this.is_only_ttm) {
            classes.push("project-milestones-display-only-ttm");
        }

        return classes;
    }
}
</script>
