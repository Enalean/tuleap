<!--
  - Copyright Enalean (c) 2019 - Present. All rights reserved.
  -
  -  Tuleap and Enalean names and logos are registrated trademarks owned by
  -  Enalean SAS. All other trademarks or names are properties of their respective
  -  owners.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
    <div class="timetracking-overview-form-trackers-selected">
        <span
            class="tlp-badge-primary tlp-badge-outline"
            v-for="tracker of overview_store.selected_trackers"
            v-bind:key="tracker.id"
        >
            <button
                type="button"
                class="tlp-badge-remove-button"
                v-on:click="removeTracker(tracker)"
                data-test="remove-tracker"
            >
                ×
            </button>
            {{ tracker.label }}
            <i class="fa-solid fa-archive timetracking-archive" aria-hidden="true"></i>
            {{ tracker.project.label }}
        </span>
    </div>
</template>

<script>
import { inject } from "vue";
import { useOverviewWidgetStore } from "../../store/index";

export default {
    name: "TimeTrackingOverviewTrackerList",
    setup: () => {
        const overview_store = useOverviewWidgetStore(inject("report_id"))();
        return { overview_store };
    },
    methods: {
        removeTracker(tracker) {
            this.overview_store.removeSelectedTracker(tracker);
        },
    },
};
</script>
