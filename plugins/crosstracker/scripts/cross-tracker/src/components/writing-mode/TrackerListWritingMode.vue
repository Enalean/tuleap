<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div class="cross-tracker-form-trackers-selected">
        <span
            class="tlp-badge-primary tlp-badge-outline"
            v-for="tracker of props.trackers"
            v-bind:key="tracker.tracker_id"
        >
            <button
                type="button"
                class="tlp-badge-remove-button"
                v-on:click="removeTracker(tracker)"
                data-test="remove-tracker"
            >
                ×
            </button>
            {{ tracker.tracker_label }}
            <i aria-hidden="true" class="fa-solid fa-archive cross-tracker-report-archive-icon"></i>
            {{ tracker.project_label }}
        </span>
    </div>
</template>
<script setup lang="ts">
import type { TrackerToUpdate } from "../../type";

const props = defineProps<{ trackers: ReadonlyArray<TrackerToUpdate> }>();
const emit = defineEmits<{ (e: "tracker-removed", tracker: TrackerToUpdate): void }>();

function removeTracker(tracker: TrackerToUpdate): void {
    emit("tracker-removed", tracker);
}
</script>
