/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

(<template>
    <div class="cross-tracker-reading-mode">
        <div class="reading-mode-report"
            v-bind:class="{'disabled': is_user_anonymous}"
            v-on:click="switchToWritingMode"
        >
            <tracker-list-reading-mode
                v-bind:reading-cross-tracker-report="readingCrossTrackerReport"
            ></tracker-list-reading-mode>
            <div class="reading-mode-query"
                v-if="is_expert_query_not_empty"
            >{{ readingCrossTrackerReport.expert_query }}</div>
        </div>
        <div class="reading-mode-actions"
            v-if="! isReportSaved"
        >
            <button class="tlp-button-primary tlp-button-outline reading-mode-actions-cancel"
                    v-on:click="cancelReport"
            >
                {{ cancel }}
            </button>
            <button class="tlp-button-primary"
                    v-on:click="saveReport"
                    v-bind:class="{'disabled': is_save_disabled}"
            >
                <i v-if="is_loading" class="tlp-button-icon fa fa-spinner fa-spin"></i>
                {{ save }}
            </button>
        </div>
    </div>
</template>)
(<script>
import TrackerListReadingMode from "./TrackerListReadingMode.vue";
import { gettext_provider } from "../gettext-provider.js";
import { isAnonymous } from "../user-service.js";
import { updateReport } from "../rest-querier.js";

export default {
    components: { TrackerListReadingMode },
    props: [
        "backendCrossTrackerReport",
        "readingCrossTrackerReport",
        "isReportSaved",
        "isReportInError",
        "reportId"
    ],
    data() {
        return {
            is_loading: false
        };
    },
    computed: {
        save: () => gettext_provider.gettext("Save report"),
        cancel: () => gettext_provider.gettext("Cancel"),
        is_user_anonymous() {
            return isAnonymous();
        },
        is_expert_query_not_empty() {
            return this.readingCrossTrackerReport.expert_query !== "";
        },
        is_save_disabled() {
            return this.is_loading || this.isReportInError;
        }
    },
    methods: {
        switchToWritingMode() {
            if (this.is_user_anonymous) {
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
                    this.reportId,
                    tracker_ids,
                    new_expert_query
                );
                this.backendCrossTrackerReport.init(trackers, expert_query);

                this.$emit("saved");
            } catch (error) {
                this.$emit("restError", error);
                throw error;
            } finally {
                this.is_loading = false;
            }
        },

        cancelReport() {
            this.readingCrossTrackerReport.duplicateFromReport(this.backendCrossTrackerReport);
            this.$emit("cancelled");
        }
    }
};
</script>)
