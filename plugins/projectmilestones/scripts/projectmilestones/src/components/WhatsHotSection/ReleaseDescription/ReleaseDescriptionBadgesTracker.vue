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
    <div v-if="display_badges_trackers_section" class="release-number-artifact">
        <h2 class="tlp-pane-subtitle" data-test="subtitle-tracker">{{ $gettext("Content") }}</h2>
        <div class="release-number-artifact-container">
            <div
                v-for="tracker in trackers_to_display"
                v-bind:key="tracker.id"
                class="release-number-artifacts-tracker"
                v-bind:class="['release-number-artifacts-tracker-' + tracker.color_name]"
                v-bind:data-test="`color-name-tracker-${tracker.id}`"
            >
                <span class="release-number-artifacts-value" data-test="total-artifact-tracker">
                    {{ tracker.total_artifact }}
                </span>
                <span
                    class="tlp-tooltip tlp-tooltip-top"
                    v-bind:data-tlp-tooltip="tracker.label"
                    v-bind:data-test="`badges-tracker-tooltip-${tracker.id}`"
                >
                    <span class="release-number-artifacts-text" data-test="artifact-tracker-name">
                        {{ tracker.label }}
                    </span>
                </span>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed } from "vue";
import type { MilestoneData, TrackerNumberArtifacts } from "../../../type";

const props = defineProps<{ release_data: MilestoneData }>();

const trackers_to_display = computed((): TrackerNumberArtifacts[] => {
    return props.release_data.number_of_artifact_by_trackers.filter(
        (tracker) => tracker.total_artifact > 0,
    );
});

const display_badges_trackers_section = computed((): boolean => {
    return trackers_to_display.value.length > 0;
});
</script>
