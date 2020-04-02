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
    <label for="move-artifact-tracker-selector" v-bind:title="selector_title">
        <translate>Destination tracker</translate>
        <span class="highlight">*</span>
        <select
            id="move-artifact-tracker-selector"
            name="move-artifact-tracker-selector"
            data-test="move-artifact-tracker-selector"
            v-bind:disabled="is_tracker_list_empty"
            v-model="selected_tracker"
        >
            <option disabled selected="selected" v-bind:value="{ tracker_id: null }">
                <translate>Choose tracker...</translate>
            </option>
            <option
                v-for="tracker of tracker_list_with_disabled_from"
                v-bind:key="tracker.id"
                v-bind:value="{
                    tracker_id: tracker.id,
                    label: tracker.label,
                    project: tracker.project,
                    color_name: tracker.color_name,
                }"
                v-bind:disabled="tracker.disabled"
            >
                {{ tracker.label }}
            </option>
        </select>
    </label>
</template>

<script>
import { mapGetters } from "vuex";

export default {
    name: "TrackerSelector",
    computed: {
        ...mapGetters(["tracker_list_with_disabled_from"]),
        is_tracker_list_empty() {
            return this.tracker_list_with_disabled_from.length === 0;
        },
        does_tracker_list_contain_from_tracker() {
            return this.tracker_list_with_disabled_from.some(({ disabled }) => disabled === true);
        },
        selected_tracker: {
            get() {
                return this.$store.state.selected_tracker;
            },
            set(tracker) {
                this.$store.commit("saveSelectedTracker", tracker);
            },
        },
        selector_title() {
            return this.does_tracker_list_contain_from_tracker
                ? this.$gettext("An artifact cannot be moved in the same tracker")
                : "";
        },
    },
};
</script>
