<!--
  - Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
        <div class="tlp-alert-danger cross-tracker-report-error" v-if="has_error === true">
            {{ error_message }}
        </div>
        <div class="tlp-alert-info cross-tracker-report-success" v-if="has_success_message">
            {{ success_message }}
        </div>
        <div class="cross-tracker-loader" v-if="is_loading"></div>
        <reading-mode
            ref="reading_mode"
            v-if="is_reading_mode_shown"
            v-bind:backend-cross-tracker-report="backendCrossTrackerReport"
            v-bind:reading-cross-tracker-report="readingCrossTrackerReport"
            v-bind:is-report-saved="is_saved"
            v-bind:is-report-in-error="has_error"
            v-bind:report-id="reportId"
            v-on:switchToWritingMode="switchToWritingMode"
            v-on:saved="reportSaved"
            v-on:cancelled="reportCancelled"
            v-on:restError="showRestError"
        ></reading-mode>
        <writing-mode
            v-if="! reading_mode"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
            v-on:switchToReadingMode="switchToReadingMode"
            v-on:error="showError"
            v-on:clearErrors="hideFeedbacks"
        ></writing-mode>
        <artifact-table
            v-if="! is_loading"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
            v-bind:is-report-saved="is_saved"
            v-bind:is-report-in-reading-mode="reading_mode"
            v-bind:report-id="reportId"
            v-on:restError="showRestError"
        ></artifact-table>
    </div>
</template>
<script>
import ArtifactTable from "./ArtifactTable.vue";
import { mapState } from "vuex";
import ReadingMode from "./reading-mode/ReadingMode.vue";
import WritingMode from "./writing-mode/WritingMode.vue";
import { isAnonymous } from "./user-service.js";
import { getReport } from "./rest-querier.js";

export default {
    components: { ArtifactTable, ReadingMode, WritingMode },
    name: "CrossTrackerWidget",
    props: {
        backendCrossTrackerReport: Object,
        readingCrossTrackerReport: Object,
        writingCrossTrackerReport: Object,
        reportId: String
    },
    data() {
        return {
            is_loading: true,
            is_saved: true,
            error_message: null,
            success_message: null
        };
    },
    computed: {
        ...mapState(["reading_mode"]),
        is_user_anonymous() {
            return isAnonymous();
        },
        is_reading_mode_shown() {
            return this.reading_mode === true && !this.is_loading;
        },
        has_error() {
            return this.error_message !== null;
        },
        has_success_message() {
            return this.success_message !== null;
        }
    },
    mounted() {
        this.loadBackendReport();
    },
    methods: {
        switchToWritingMode() {
            if (this.is_user_anonymous) {
                return;
            }

            this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
            this.hideFeedbacks();
            this.$store.commit("switchToWritingMode");
        },

        switchToReadingMode({ saved_state }) {
            if (saved_state === true) {
                this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
            } else {
                this.readingCrossTrackerReport.duplicateFromReport(this.writingCrossTrackerReport);
            }
            this.hideFeedbacks();
            this.is_saved = saved_state;
            this.$store.commit("switchToReadingMode");
        },

        async loadBackendReport() {
            this.is_loading = true;
            try {
                const { trackers, expert_query } = await getReport(this.reportId);
                this.backendCrossTrackerReport.init(trackers, expert_query);
                this.initReports();
            } catch (error) {
                this.showRestError(error);
                throw error;
            } finally {
                this.is_loading = false;
            }
        },

        initReports() {
            this.readingCrossTrackerReport.duplicateFromReport(this.backendCrossTrackerReport);
            this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
        },

        hideFeedbacks() {
            this.error_message = null;
            this.success_message = null;
        },

        reportSaved() {
            this.initReports();
            this.hideFeedbacks();
            this.is_saved = true;
            this.success_message = this.$gettext("Report has been successfully saved");
        },

        reportCancelled() {
            this.hideFeedbacks();
            this.is_saved = true;
        },

        showError(error) {
            this.error_message = error;
        },

        showRestError(rest_error) {
            if (!rest_error.response) {
                this.error_message = this.$gettext("An error occured");
                return;
            }

            return rest_error.response.json().then(
                error_details => {
                    if ("i18n_error_message" in error_details.error) {
                        this.error_message = error_details.error.i18n_error_message;
                    }
                },
                error => {
                    this.error_message = this.$gettext("An error occured");
                }
            );
        }
    }
};
</script>
