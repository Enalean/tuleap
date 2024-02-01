<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <div class="timetracking-writing-mode-selected-tracker">
        <div
            class="tlp-form-element timetracking-writing-mode-selected-project"
            v-bind:class="{ 'tlp-form-element-disabled': is_project_select_disabled }"
        >
            <label class="tlp-label">
                {{ $gettext("Project") }}
                <i class="fa fa-asterisk"></i>
            </label>
            <time-tracking-overview-project-option v-bind:projects="projects" />
        </div>
        <div
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': is_project_select_disabled }"
        >
            <label class="tlp-label">
                {{ $gettext("Tracker") }}
                <i class="fa fa-asterisk"></i>
            </label>
            <div class="tlp-form-element tlp-form-element-append">
                <time-tracking-overview-trackers-options v-on:input="trackerSelected($event)" />
                <button
                    type="button"
                    class="tlp-append tlp-button-primary tlp-button-outline"
                    v-bind:disabled="!is_tracker_available"
                    v-on:click="addTracker()"
                    data-test="add-tracker-button"
                >
                    <i
                        v-if="is_tracker_or_project_select_disabled"
                        class="tlp-button-icon fa fa-spinner fa-spin"
                        data-test="icon-spinner"
                    ></i>
                    <i
                        v-else-if="is_tracker_available"
                        class="tlp-button-icon fa fa-plus"
                        data-test="icon-plus"
                    ></i>
                    <i v-else class="tlp-button-icon fa fa-ban" data-test="icon-ban"></i>
                    {{ $gettext("Add") }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import TimeTrackingOverviewProjectOption from "./TimeTrackingOverviewProjectOption.vue";
import TimeTrackingOverviewTrackersOptions from "./TimeTrackingOverviewTrackersOptions.vue";
import { mapState } from "vuex";
export default {
    name: "TimeTrackingOverviewWritingTrackers",
    components: { TimeTrackingOverviewProjectOption, TimeTrackingOverviewTrackersOptions },
    data() {
        return {
            selected_tracker: null,
        };
    },
    computed: {
        ...mapState(["projects", "trackers", "is_loading_tracker"]),
        is_project_select_disabled() {
            return this.projects.length === 0;
        },
        is_tracker_or_project_select_disabled() {
            return (
                (this.trackers.length === 0 || this.projects.length === 0) &&
                this.is_loading_tracker
            );
        },
        is_tracker_available() {
            return this.trackers.length > 0 && !this.is_loading_tracker;
        },
    },
    methods: {
        trackerSelected(value) {
            this.selected_tracker = value;
        },
        addTracker() {
            this.$store.commit("addSelectedTrackers", this.selected_tracker);
        },
    },
};
</script>
