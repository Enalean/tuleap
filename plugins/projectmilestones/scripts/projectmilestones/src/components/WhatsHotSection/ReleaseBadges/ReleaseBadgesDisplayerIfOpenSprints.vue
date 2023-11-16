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
    <div
        class="project-release-infos-badges"
        v-bind:class="{
            'on-open-sprints-details':
                open_sprints_details || only_one_open_sprint_and_no_closed_sprints,
            'can-close-sprint': !only_one_open_sprint_and_no_closed_sprints,
        }"
    >
        <div
            class="project-release-badges-open-closed"
            v-bind:class="{
                'open-badges-sprints':
                    open_sprints_details || only_one_open_sprint_and_no_closed_sprints,
            }"
        >
            <div
                v-if="display_badge_all_sprint"
                class="project-release-infos-badges-all-sprint-badges"
            >
                <release-badges-all-sprints
                    v-if="should_display_all_sprints_badge"
                    v-bind:release_data="release_data"
                    v-bind:is-past-release="isPastRelease"
                    v-on:on-click-open-sprints-details="on_click_open_sprints_details()"
                    data-test="badge-sprint"
                />
                <release-badges-open-sprint
                    v-else
                    v-for="sprint in release_data.open_sprints"
                    v-bind:key="sprint.id"
                    v-bind:sprint_data="sprint"
                    v-bind:is-past-release="isPastRelease"
                />
            </div>
            <i
                v-if="open_sprints_details"
                v-on:click="on_click_close_sprints_details"
                class="icon-badge-sprint-to-close fa"
                data-test="button-to-close"
            />
            <release-badges-closed-sprints
                v-if="
                    closed_sprints_exist &&
                    open_sprints_details &&
                    root_store.user_can_view_sub_milestones_planning
                "
                v-bind:release_data="release_data"
            />
        </div>
        <hr
            v-if="open_sprints_details || only_one_open_sprint_and_no_closed_sprints"
            data-test="line-displayed"
            class="milestone-badges-sprints-separator"
        />
        <release-others-badges v-bind:release_data="release_data" />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import type { MilestoneData } from "../../../type";
import ReleaseBadgesAllSprints from "./ReleaseBadgesAllSprints.vue";
import ReleaseOthersBadges from "./ReleaseOthersBadges.vue";
import ReleaseBadgesClosedSprints from "./ReleaseBadgesClosedSprints.vue";
import { getTrackerSubmilestoneLabel } from "../../../helpers/tracker-label-helper";
import { openSprintsExist, closedSprintsExists } from "../../../helpers/milestones-sprints-helper";
import ReleaseBadgesOpenSprint from "./ReleaseBadgesOpenSprint.vue";
import { useStore } from "../../../stores/root";

const root_store = useStore();

const props = defineProps<{
    release_data: MilestoneData;
    isOpen: boolean;
    isPastRelease: boolean;
}>();

const open_sprints_details = ref(false);

onMounted((): void => {
    open_sprints_details.value = props.isOpen;
});

const display_badge_all_sprint = computed((): boolean => {
    return (
        openSprintsExist(props.release_data) &&
        getTrackerSubmilestoneLabel(props.release_data) !== "" &&
        root_store.user_can_view_sub_milestones_planning
    );
});

const closed_sprints_exist = computed((): boolean => {
    return (
        closedSprintsExists(props.release_data) &&
        getTrackerSubmilestoneLabel(props.release_data) !== ""
    );
});

const only_one_open_sprint_and_no_closed_sprints = computed((): boolean => {
    return (
        !closed_sprints_exist.value &&
        props.release_data.open_sprints !== null &&
        props.release_data.open_sprints.length === 1
    );
});
const should_display_all_sprints_badge = computed((): boolean => {
    return !open_sprints_details.value && !only_one_open_sprint_and_no_closed_sprints.value;
});

function on_click_close_sprints_details(): void {
    if (!only_one_open_sprint_and_no_closed_sprints.value) {
        open_sprints_details.value = false;
    }
}

function on_click_open_sprints_details(): void {
    if (!only_one_open_sprint_and_no_closed_sprints.value) {
        open_sprints_details.value = true;
    }
}
</script>
