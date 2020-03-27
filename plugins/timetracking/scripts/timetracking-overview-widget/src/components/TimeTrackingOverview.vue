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
    <div class="cross-timetracking-widget">
        <div
            class="tlp-alert-info cross-tracker-report-success"
            v-if="has_success_message"
            data-test="report-success"
        >
            {{ success_message }}
        </div>
        <time-tracking-overview-reading-mode v-if="reading_mode" data-test="reading-mode" />
        <time-tracking-overview-writing-mode v-else data-test="writing-mode" />
        <time-tracking-overview-table />
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import TimeTrackingOverviewReadingMode from "./reading-mode/TimeTrackingOverviewReadingMode.vue";
import TimeTrackingOverviewWritingMode from "./writing-mode/TimeTrackingOverviewWritingMode.vue";
import TimeTrackingOverviewTable from "./TimeTrackingOverviewTable.vue";

export default {
    name: "TimeTrackingOverview",
    components: {
        TimeTrackingOverviewTable,
        TimeTrackingOverviewReadingMode,
        TimeTrackingOverviewWritingMode,
    },
    props: {
        reportId: String,
        userId: Number,
        areVoidTrackersHidden: Boolean,
    },
    computed: {
        ...mapState(["reading_mode", "success_message"]),
        ...mapGetters(["has_success_message"]),
    },
    mounted() {
        this.$store.commit("setReportId", this.reportId);
        this.$store.commit("initUserId", this.userId);
        this.$store.commit("setDisplayVoidTrackers", this.areVoidTrackersHidden);
        this.$store.dispatch("initWidgetWithReport");
        this.$store.dispatch("getProjects");
        document.addEventListener("timeUpdated", this.reloadTimes);
    },
    destroyed() {
        document.removeEventListener("timeUpdated", this.reloadTimes);
    },
    methods: {
        reloadTimes() {
            this.$store.dispatch("reloadTimetrackingOverviewTimes");
        },
    },
};
</script>
