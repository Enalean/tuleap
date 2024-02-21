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
        class="project-release-closed-sprint-badge project-release-info-badge tlp-badge-secondary tlp-badge-outline"
        v-if="display_closed_badge"
        data-test="total-closed-sprints"
    >
        <i class="fa fa-map-signs tlp-badge-icon"></i>
        {{ closed_sprints_label }}
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { MilestoneData } from "../../../type";
import { getTrackerSubmilestoneLabel } from "../../../helpers/tracker-label-helper";
import { useStore } from "../../../stores/root";
import { useGettext } from "vue3-gettext";

const root_store = useStore();

const props = defineProps<{
    release_data: MilestoneData;
}>();

const { $ngettext, interpolate } = useGettext();

const tracker_submilestone_label = computed((): string => {
    return getTrackerSubmilestoneLabel(props.release_data);
});
const display_closed_badge = computed((): boolean => {
    if (
        typeof props.release_data.total_sprint !== "number" ||
        tracker_submilestone_label.value === ""
    ) {
        return false;
    }

    return (
        props.release_data.total_sprint > 0 &&
        typeof props.release_data.total_closed_sprint === "number" &&
        root_store.user_can_view_sub_milestones_planning
    );
});
const closed_sprints_label = computed((): string => {
    const closed_sprints = props.release_data.total_closed_sprint ?? 0;
    const translated = $ngettext(
        "%{total_closed_sprint} closed %{tracker_label}",
        "%{total_closed_sprint} closed %{tracker_label}",
        closed_sprints,
    );

    return interpolate(translated, {
        total_closed_sprint: props.release_data.total_closed_sprint,
        tracker_label: tracker_submilestone_label.value,
    });
});
</script>
