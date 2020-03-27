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
            />
            <div class="reading-mode-query" v-if="is_expert_query_not_empty">
                {{ readingCrossTrackerReport.expert_query }}
            </div>
        </div>
        <div class="reading-mode-actions" v-if="!is_report_saved">
            <button
                class="tlp-button-primary tlp-button-outline reading-mode-actions-cancel"
                v-on:click="cancelReport()"
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
                <i v-if="!is_loading" class="tlp-button-icon fa fa-save"></i>
                <i v-if="is_loading" class="tlp-button-icon fa fa-circle-o-notch fa-spin"></i>
                <translate>Save report</translate>
            </button>
        </div>
    </div>
</template>
<script>
import { mapState } from "vuex";
import TrackerListReadingMode from "./TrackerListReadingMode.vue";
import { updateReport } from "../api/rest-querier.js";

export default {
    components: { TrackerListReadingMode },
    props: {
        backendCrossTrackerReport: Object,
        readingCrossTrackerReport: Object,
    },
    data() {
        return {
            is_loading: false,
        };
    },
    computed: {
        ...mapState(["is_report_saved", "report_id", "is_user_admin"]),
        is_expert_query_not_empty() {
            return this.readingCrossTrackerReport.expert_query !== "";
        },
        is_save_disabled() {
            return this.is_loading || this.$store.getters.has_error_message;
        },
    },
    methods: {
        switchToWritingMode() {
            if (!this.is_user_admin) {
                return;
            }

            this.$emit("switchToWritingMode");
        },

        async saveReport() {
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
                    new_expert_query
                );
                this.backendCrossTrackerReport.init(trackers, expert_query);

                this.$emit("saved");
            } catch (error) {
                if (Object.prototype.hasOwnProperty.call(error, "response")) {
                    const error_json = await error.response.json();
                    this.$store.commit("setErrorMessage", error_json.error.message);
                }
            } finally {
                this.is_loading = false;
            }
        },

        cancelReport() {
            this.readingCrossTrackerReport.duplicateFromReport(this.backendCrossTrackerReport);
            this.$store.commit("discardUnsavedReport");
        },
    },
};
</script>
