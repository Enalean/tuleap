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

<script setup lang="ts">
import { computed, ref } from "vue";
import type { MilestoneData } from "../../../type";
import ReleaseDescriptionBadgesTracker from "./ReleaseDescriptionBadgesTracker.vue";
import ChartDisplayer from "./Chart/ChartDisplayer.vue";
import TestManagementDisplayer from "./TestManagement/TestManagementDisplayer.vue";
import { is_testplan_activated } from "../../../helpers/test-management-helper";

const props = defineProps<{ release_data: MilestoneData }>();

let is_burndown = ref(false);
let is_burnup = ref(false);
let is_ttm = ref(false);

const is_testmanagement_available = computed((): boolean => {
    return is_testplan_activated(props.release_data);
});
const display_badges_tracker = computed((): boolean => {
    const trackers_to_display = props.release_data.number_of_artifact_by_trackers.filter(
        (tracker) => tracker.total_artifact > 0,
    );
    return trackers_to_display.length > 0;
});

function burndown_exists(): void {
    is_burndown.value = true;
}

function burnup_exists(): void {
    is_burnup.value = true;
}

function ttm_chart_exists(): void {
    is_ttm.value = true;
}

const is_description = computed((): boolean => {
    return (
        props.release_data.post_processed_description !== null &&
        props.release_data.post_processed_description !== ""
    );
});
const are_only_trackers = computed((): boolean => {
    return !is_description.value && !is_burnup.value && !is_burndown.value && !is_ttm.value;
});

const is_only_burndown = computed((): boolean => {
    return is_burndown.value && !is_burnup.value && !is_ttm.value;
});
const is_only_burnup = computed((): boolean => {
    return !is_burndown.value && is_burnup.value && !is_ttm.value;
});
const is_only_ttm = computed((): boolean => {
    return !is_burndown.value && !is_burnup.value && is_ttm.value;
});
const is_only_one_chart = computed((): boolean => {
    return is_only_burndown.value || is_only_burnup.value || is_only_ttm.value;
});
const display_badges_on_line = computed((): boolean => {
    if (is_description.value) {
        return false;
    }

    return are_only_trackers.value || is_only_one_chart.value;
});

const classes = computed((): string[] => {
    const classes: string[] = [];

    if (display_badges_on_line.value) {
        classes.push("project-milestones-display-on-line-tracker");
    }

    if (are_only_trackers.value) {
        classes.push("project-milestones-only-trackers-to-display");
    }

    if (is_description.value) {
        classes.push("project-milestones-display-description");
    }

    if (is_ttm.value) {
        classes.push("project-milestones-display-ttm-chart");
    }

    if (is_burndown.value) {
        classes.push("project-milestones-display-burndown-chart");
    }

    if (is_burnup.value) {
        classes.push("project-milestones-display-burnup-chart");
    }

    if (is_only_ttm.value) {
        classes.push("project-milestones-display-only-ttm");
    }

    return classes;
});
</script>
