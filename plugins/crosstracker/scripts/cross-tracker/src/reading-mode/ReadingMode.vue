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
    <div class="cross-tracker-reading-mode">
        <div
            class="reading-mode-report"
            v-bind:class="{ disabled: !is_user_admin }"
            v-on:click="switchToWritingMode"
            data-test="cross-tracker-reading-mode"
        >
            <tracker-list-reading-mode
                v-bind:reading-cross-tracker-report="readingCrossTrackerReport"
                data-test="tracker-list-reading-mode"
            />
            <div
                class="reading-mode-query"
                v-if="is_expert_query_not_empty"
                data-test="tql-reading-mode-query"
            >
                {{ readingCrossTrackerReport.expert_query }}
            </div>
        </div>
        <div class="reading-mode-actions" v-if="!is_report_saved">
            <button
                class="tlp-button-primary tlp-button-outline reading-mode-actions-cancel"
                v-on:click="cancelReport()"
                data-test="cross-tracker-cancel-report"
                v-translate
            >
                Cancel
            </button>
            <button
                class="tlp-button-primary"
                v-on:click="saveReport()"
                v-bind:class="{ disabled: is_save_disabled }"
                data-test="cross-tracker-save-report"
            >
                <i
                    aria-hidden="true"
                    class="tlp-button-icon fa-solid"
                    v-bind:class="{
                        'fa-circle-notch fa-spin': is_loading,
                        'fa-save': !is_loading,
                    }"
                ></i>
                <translate>Save report</translate>
            </button>
        </div>
    </div>
</template>
<script lang="ts">
import TrackerListReadingMode from "./TrackerListReadingMode.vue";
import { updateReport } from "../api/rest-querier";
import type ReadingCrossTrackerReport from "./reading-cross-tracker-report";
import Component from "vue-class-component";
import { Prop } from "vue-property-decorator";
import { State } from "vuex-class";
import type BackendCrossTrackerReport from "../backend-cross-tracker-report";
import Vue from "vue";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

@Component({
    components: { TrackerListReadingMode },
})
export default class ReadingMode extends Vue {
    @Prop({ required: true })
    readonly readingCrossTrackerReport!: ReadingCrossTrackerReport;

    @Prop({ required: true })
    readonly backendCrossTrackerReport!: BackendCrossTrackerReport;

    @State
    readonly is_report_saved!: boolean;

    @State
    private readonly report_id!: number;

    @State
    readonly is_user_admin!: boolean;

    is_loading = false;

    get is_save_disabled(): boolean {
        return this.is_loading || this.$store.getters.has_error_message;
    }

    get is_expert_query_not_empty(): boolean {
        return this.readingCrossTrackerReport.expert_query !== "";
    }

    switchToWritingMode(): void {
        if (!this.is_user_admin) {
            return;
        }

        this.$emit("switch-to-writing-mode");
    }

    async saveReport(): Promise<void> {
        if (this.is_save_disabled) {
            return;
        }

        this.is_loading = true;

        this.backendCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
        const tracker_ids = this.backendCrossTrackerReport.getTrackerIds();
        const new_expert_query = this.backendCrossTrackerReport.getExpertQuery();
        try {
            const { trackers, expert_query } = await updateReport(
                this.report_id,
                tracker_ids,
                new_expert_query,
            );
            this.backendCrossTrackerReport.init(trackers, expert_query);

            this.$emit("saved");
        } catch (error) {
            if (error instanceof FetchWrapperError) {
                const error_json = await error.response.json();
                this.$store.commit("setErrorMessage", error_json.error.message);
            }
        } finally {
            this.is_loading = false;
        }
    }

    cancelReport(): void {
        this.readingCrossTrackerReport.duplicateFromReport(this.backendCrossTrackerReport);
        this.$store.commit("discardUnsavedReport");
    }
}
</script>
