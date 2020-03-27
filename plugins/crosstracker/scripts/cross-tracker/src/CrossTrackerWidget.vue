<!--
  - Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
    <div>
        <error-message />
        <error-inactive-project-message />
        <div
            class="tlp-alert-info cross-tracker-report-success"
            v-if="has_success_message"
            data-test="cross-tracker-report-success"
        >
            {{ success_message }}
        </div>
        <div class="cross-tracker-loader" v-if="is_loading"></div>
        <reading-mode
            v-if="is_reading_mode_shown"
            v-bind:backend-cross-tracker-report="backendCrossTrackerReport"
            v-bind:reading-cross-tracker-report="readingCrossTrackerReport"
            v-on:switchToWritingMode="switchToWritingMode"
            v-on:saved="reportSaved"
        />
        <writing-mode
            v-if="!reading_mode"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
            v-on:switchToReadingMode="switchToReadingMode"
        />
        <artifact-table
            v-if="!is_loading"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
        />
    </div>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import ArtifactTable from "./components/ArtifactTable.vue";
import ReadingMode from "./reading-mode/ReadingMode.vue";
import WritingMode from "./writing-mode/WritingMode.vue";
import ErrorMessage from "./components/ErrorMessage.vue";
import ErrorInactiveProjectMessage from "./components/ErrorInactiveProjectMessage.vue";
import { getReport } from "./api/rest-querier.js";

export default {
    name: "CrossTrackerWidget",
    components: {
        ErrorMessage,
        ArtifactTable,
        ReadingMode,
        WritingMode,
        ErrorInactiveProjectMessage,
    },
    props: {
        backendCrossTrackerReport: Object,
        readingCrossTrackerReport: Object,
        writingCrossTrackerReport: Object,
    },
    data() {
        return {
            is_loading: true,
        };
    },
    computed: {
        ...mapState([
            "reading_mode",
            "is_report_saved",
            "report_id",
            "success_message",
            "is_user_admin",
        ]),
        ...mapGetters(["has_success_message"]),
        is_reading_mode_shown() {
            return this.reading_mode === true && !this.is_loading;
        },
    },
    mounted() {
        this.loadBackendReport();
    },
    methods: {
        switchToWritingMode() {
            if (!this.is_user_admin) {
                return;
            }

            this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
            this.$store.commit("switchToWritingMode");
        },

        switchToReadingMode({ saved_state }) {
            if (saved_state === true) {
                this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
            } else {
                this.readingCrossTrackerReport.duplicateFromReport(this.writingCrossTrackerReport);
            }
            this.$store.commit("switchToReadingMode", { saved_state });
        },

        async loadBackendReport() {
            this.is_loading = true;
            try {
                const { trackers, expert_query, invalid_trackers } = await getReport(
                    this.report_id
                );
                this.backendCrossTrackerReport.init(trackers, expert_query);
                this.initReports();

                if (invalid_trackers.length > 0) {
                    this.$store.commit("setInvalidTrackers", invalid_trackers);
                }
            } catch (error) {
                if (Object.prototype.hasOwnProperty.call(error, "response")) {
                    const error_json = await error.response.json();
                    this.$store.commit("setErrorMessage", error_json.error.message);
                }
            } finally {
                this.is_loading = false;
            }
        },

        initReports() {
            this.readingCrossTrackerReport.duplicateFromReport(this.backendCrossTrackerReport);
            this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
        },

        reportSaved() {
            this.initReports();
            this.$store.commit("resetInvalidTrackerList");
            this.$store.commit(
                "switchReportToSaved",
                this.$gettext("Report has been successfully saved")
            );
        },
    },
};
</script>
