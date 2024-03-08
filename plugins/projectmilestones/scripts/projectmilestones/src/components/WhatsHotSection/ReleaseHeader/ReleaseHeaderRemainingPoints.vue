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
            <span class="release-remaining-text">{{ pts_to_go_label }}</span>
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

<script setup lang="ts">
import { computed } from "vue";
import type { MilestoneData } from "../../../type";
import { useGettext } from "vue3-gettext";

const { $ngettext, $gettext, interpolate } = useGettext();

const props = defineProps<{
    release_data: MilestoneData;
}>();

function formatPoints(pts: number | null): number {
    return pts ?? 0;
}

const disabled_points = computed((): boolean => {
    return (
        typeof props.release_data.remaining_effort !== "number" ||
        !props.release_data.initial_effort ||
        props.release_data.initial_effort < props.release_data.remaining_effort
    );
});

const are_all_effort_defined = computed((): boolean => {
    if (
        typeof props.release_data.remaining_effort !== "number" ||
        typeof props.release_data.initial_effort !== "number"
    ) {
        return false;
    }
    return (
        props.release_data.remaining_effort > 0 &&
        props.release_data.initial_effort > 0 &&
        props.release_data.initial_effort >= props.release_data.remaining_effort
    );
});

const get_tooltip_effort_points = computed((): string => {
    const remaining_effort = props.release_data.remaining_effort;
    const initial_effort = props.release_data.initial_effort;

    if (typeof remaining_effort !== "number") {
        return $gettext("No remaining effort defined.");
    }

    if (typeof initial_effort !== "number") {
        return $gettext("No initial effort defined.");
    }

    if (initial_effort === 0) {
        return $gettext("Initial effort equal at 0.");
    }

    if (initial_effort < remaining_effort) {
        return interpolate(
            $gettext(
                "Initial effort (%{initial_effort}) should be bigger or equal to remaining effort (%{remaining_effort}).",
            ),
            {
                initial_effort,
                remaining_effort,
            },
        );
    }

    return (
        (((initial_effort - remaining_effort) / initial_effort) * 100).toFixed(2).toString() + "%"
    );
});
const pts_to_go_label = computed((): string => {
    const remaining_effort = props.release_data.remaining_effort ?? 0;
    return $ngettext("pt to go", "pts to go", remaining_effort);
});
</script>
