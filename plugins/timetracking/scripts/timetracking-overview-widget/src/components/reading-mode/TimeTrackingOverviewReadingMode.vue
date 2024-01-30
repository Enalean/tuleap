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
    <div>
        <div
            class="timetracking-reading-mode"
            data-test="overview-toggle-reading-mode"
            v-on:click="toggleReadingMode()"
        >
            <time-tracking-overview-reading-dates />
            <time-tracking-overview-tracker-list />
        </div>
        <div class="reading-mode-actions" v-if="!is_report_saved" data-test="reading-mode-actions">
            <button
                class="tlp-button-primary tlp-button-outline reading-mode-actions-cancel"
                v-on:click="discardReport()"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                class="tlp-button-primary"
                v-on:click="saveReport()"
                data-test="save-overview-report"
            >
                <i
                    v-if="is_loading"
                    class="tlp-button-icon fa fa-spinner fa-spin"
                    data-test="icon-spinner"
                ></i>
                {{ $gettext("Save report") }}
            </button>
        </div>
    </div>
</template>

<script>
import { mapMutations, mapState } from "vuex";
import TimeTrackingOverviewTrackerList from "./TimeTrackingOverviewTrackerList.vue";
import TimeTrackingOverviewReadingDates from "./TimeTrackingOverviewReadingDates.vue";

export default {
    name: "TimeTrackingOverviewReadingMode",
    components: { TimeTrackingOverviewTrackerList, TimeTrackingOverviewReadingDates },
    computed: {
        ...mapState(["is_report_saved", "is_loading"]),
    },
    methods: {
        ...mapMutations(["toggleReadingMode"]),
        saveReport() {
            this.$store.dispatch("saveReport", this.$gettext("Report has been successfully saved"));
        },
        async discardReport() {
            await this.$store.dispatch("initWidgetWithReport");
            this.$store.commit("setIsReportSave", true);
        },
    },
};
</script>
