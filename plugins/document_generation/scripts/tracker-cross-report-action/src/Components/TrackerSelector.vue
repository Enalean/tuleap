<!--
  - Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
  -
  -->

<template>
    <div
        class="tlp-form-element"
        v-bind:class="{ 'tlp-form-element-disabled': project_id === null }"
    >
        <label class="tlp-label" v-bind:for="select_element_id">
            {{ $gettext("Tracker") }}
        </label>
        <select
            v-bind:id="select_element_id"
            v-model="tracker"
            class="tlp-select"
            v-bind:disabled="project_id === null || is_processing"
        >
            <option
                v-for="current_tracker in current_trackers"
                v-bind:key="current_tracker.id"
                v-bind:value="current_tracker"
            >
                {{ current_tracker.label }}
            </option>
        </select>
    </div>
</template>
<script lang="ts" setup>
import { computed } from "vue";
import { getTrackers as getTrackersFromAPI } from "../rest-querier";
import type { SelectedTracker } from "../type";
import type { MinimalTrackerResponse } from "@tuleap/plugin-tracker-rest-api-types";
import { usePromise } from "../Helpers/use-promise";
import { generateElementID } from "../Helpers/id-element-generator";

const props = defineProps<{ project_id: number | null; tracker: SelectedTracker | null }>();
const emit = defineEmits<{
    (e: "update:tracker", value: SelectedTracker | null): void;
}>();

const select_element_id = generateElementID();

const default_data_current_trackers: MinimalTrackerResponse[] = [];

function getTrackers(project_id: number | null): Promise<MinimalTrackerResponse[]> {
    if (project_id === null) {
        return Promise.resolve(default_data_current_trackers);
    }
    return getTrackersFromAPI(project_id);
}

const { is_processing, data: current_trackers } = usePromise(
    default_data_current_trackers,
    computed(() => getTrackers(props.project_id)),
);

const tracker = computed({
    get(): SelectedTracker | null {
        return props.tracker;
    },
    set(value: SelectedTracker | null) {
        emit("update:tracker", value);
    },
});
</script>
