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
    <select
        class="timetracking-overview-trackers-selector-input tlp-select"
        id="tracker"
        name="tracker"
        ref="select"
        v-bind:disabled="is_tracker_select_disabled"
        v-on:input="setSelected($event)"
        data-test="overview-tracker-selector"
    >
        <option v-bind:value="null">{{ $gettext("Please choose...") }}</option>
        <option
            v-for="tracker in overview_store.trackers"
            v-bind:disabled="tracker.disabled"
            v-bind:value="tracker.id"
            v-bind:key="tracker.id"
        >
            {{ tracker.label }}
        </option>
    </select>
</template>
<script>
import { inject } from "vue";
import { useOverviewWidgetStore } from "../../store/index";

export default {
    name: "TimeTrackingOverviewTrackersOptions",
    setup: () => {
        const overview_store = useOverviewWidgetStore(inject("report_id"))();
        return { overview_store };
    },
    computed: {
        is_tracker_select_disabled() {
            return this.overview_store.trackers.length === 0;
        },
    },
    watch: {
        is_added_tracker: {
            handler() {
                this.$refs.select.options.selectedIndex = 0;
            },
            deep: true,
        },
    },
    methods: {
        setSelected($event) {
            this.$emit("input", $event.target.value);
        },
    },
};
</script>
