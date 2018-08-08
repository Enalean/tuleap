<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <label for="move-artifact-tracker-selector">
        <translate>Choose tracker</translate>
        <span class="highlight">*</span>
        <select id="move-artifact-tracker-selector"
                name="move-artifact-tracker-selector"
                v-bind:disabled="isTrackerListEmpty"
                v-model="selectedTracker"
        >
            <option disabled="disabled" selected="selected" v-bind:value="{ tracker_id: null }">
                <translate>Choose tracker...</translate>
            </option>
            <option v-for="tracker of trackerList"
                    v-bind:key="tracker.id"
                    v-bind:value="{
                        tracker_id: tracker.id,
                        label: tracker.label,
                        project: tracker.project,
                        color_name: tracker.color_name
                    }"
            >
                {{ tracker.label }}
            </option>
        </select>
    </label>
</template>

<script>
import { mapState } from "vuex";

export default {
    name: "TrackerSelector",
    computed: {
        ...mapState({
            trackerList: state => state.trackers
        }),
        isTrackerListEmpty() {
            return this.trackerList.length === 0;
        },
        selectedTracker: {
            get() {
                return this.$store.state.selected_tracker;
            },
            set(tracker) {
                this.$store.commit("setErrorMessage", "");
                this.$store.commit("setSelectedTracker", tracker);
            }
        }
    }
};
</script>
