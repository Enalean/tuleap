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
            v-on:switch-to-writing-mode="switchToWritingMode"
            v-on:saved="reportSaved"
        />
        <writing-mode
            v-if="!reading_mode"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
            v-on:switch-to-reading-mode="switchToReadingMode"
        />
        <artifact-table
            v-if="!is_loading"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
        />
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import ArtifactTable from "./components/ArtifactTable.vue";
import ReadingMode from "./reading-mode/ReadingMode.vue";
import WritingMode from "./writing-mode/WritingMode.vue";
import ErrorMessage from "./components/ErrorMessage.vue";
import ErrorInactiveProjectMessage from "./components/ErrorInactiveProjectMessage.vue";
import { getReport } from "./api/rest-querier";
import ArtifactTableRow from "./components/ArtifactTableRow.vue";
import ExportButton from "./components/ExportCSVButton.vue";
import { Component, Prop } from "vue-property-decorator";
import { Getter, State } from "vuex-class";
import type WritingCrossTrackerReport from "./writing-mode/writing-cross-tracker-report";
import type BackendCrossTrackerReport from "./backend-cross-tracker-report";
import type ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

export interface SaveEvent {
    saved_state: boolean;
}

@Component({
    components: {
        ArtifactTable,
        ReadingMode,
        WritingMode,
        ErrorMessage,
        ErrorInactiveProjectMessage,
        ArtifactTableRow,
        ExportButton,
    },
})
export default class CrossTrackerWidget extends Vue {
    @Prop({ required: true })
    readonly backendCrossTrackerReport!: BackendCrossTrackerReport;
    @Prop({ required: true })
    readonly readingCrossTrackerReport!: ReadingCrossTrackerReport;
    @Prop({ required: true })
    readonly writingCrossTrackerReport!: WritingCrossTrackerReport;

    @State
    readonly reading_mode!: boolean;
    @State
    private readonly report_id!: number;
    @State
    readonly success_message!: string;
    @State
    private readonly is_user_admin!: boolean;

    @Getter
    readonly has_success_message!: boolean;

    is_loading = true;

    get is_reading_mode_shown(): boolean {
        return this.reading_mode && !this.is_loading;
    }
    mounted(): void {
        this.loadBackendReport();
    }

    switchToWritingMode(): void {
        if (!this.is_user_admin) {
            return;
        }

        this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
        this.$store.commit("switchToWritingMode");
    }

    switchToReadingMode(event: SaveEvent): void {
        if (event.saved_state) {
            this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
        } else {
            this.readingCrossTrackerReport.duplicateFromWritingReport(
                this.writingCrossTrackerReport
            );
        }
        this.$store.commit("switchToReadingMode", event.saved_state);
    }

    async loadBackendReport(): Promise<void> {
        this.is_loading = true;
        try {
            const { trackers, expert_query, invalid_trackers } = await getReport(this.report_id);
            this.backendCrossTrackerReport.init(trackers, expert_query);
            this.initReports();

            if (invalid_trackers.length > 0) {
                this.$store.commit("setInvalidTrackers", invalid_trackers);
            }
        } catch (error) {
            if (error instanceof FetchWrapperError) {
                const error_json = await error.response.json();
                this.$store.commit("setErrorMessage", error_json.error.message);
            }
        } finally {
            this.is_loading = false;
        }
    }

    initReports(): void {
        this.readingCrossTrackerReport.duplicateFromReport(this.backendCrossTrackerReport);
        this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
    }

    reportSaved(): void {
        this.initReports();
        this.$store.commit("resetInvalidTrackerList");
        this.$store.commit(
            "switchReportToSaved",
            this.$gettext("Report has been successfully saved")
        );
    }
}
</script>
