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
    <div>
        <div class="tlp-alert-danger cross-tracker-report-error" v-if="has_error === true">
            {{ error_message }}
        </div>
        <reading-mode
            ref="reading_mode"
            v-show="reading_mode"
            v-bind:backend-cross-tracker-report="backendCrossTrackerReport"
            v-bind:reading-cross-tracker-report="readingCrossTrackerReport"
            v-bind:reading-controller="readingController"
            v-on:switchToWritingMode="switchToWritingMode"
            v-on:cancelled="reportCancelled"
        ></reading-mode>
        <writing-mode
            v-if="! reading_mode"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
            v-bind:error-displayer="errorDisplayer"
            v-on:switchToReadingMode="switchToReadingMode"
        ></writing-mode>
        <artifact-table-renderer
            ref="artifact_table"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
            v-bind:saved-state="savedState"
            v-bind:report-id="reportId"
            v-on:error="showRestError"
        ></artifact-table-renderer>
    </div>
</template>)
(<script>
    import ArtifactTableRenderer from './ArtifactTableRenderer.vue';
    import ReadingMode           from './reading-mode/ReadingMode.vue';
    import WritingMode           from './writing-mode/WritingMode.vue';
    import { gettext_provider }  from './gettext-provider.js';
    import { isAnonymous }       from './user-service.js';

    export default {
        components: { ArtifactTableRenderer, ReadingMode, WritingMode },
        name: 'CrossTrackerWidget',
        props: [
            'backendCrossTrackerReport',
            'readingCrossTrackerReport',
            'writingCrossTrackerReport',
            'successDisplayer',
            'errorDisplayer',
            'savedState',
            'readingController',
            'reportId'
        ],
        data() {
            return {
                reading_mode   : true,
                error_message  : null
            };
        },
        computed: {
            is_user_anonymous() {
                return isAnonymous();
            },
            has_error() {
                return this.error_message !== null;
            },
        },
        methods: {
            switchToWritingMode() {
                if (this.is_user_anonymous) {
                    return;
                }

                this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
                this.hideFeedbacks();
                this.reading_mode = false;
            },

            switchToReadingMode({ saved_state }) {
                this.hideFeedbacks();
                if (saved_state === true) {
                    this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
                    this.savedState.switchToSavedState();
                    this.$refs.reading_mode.hideActions();
                } else {
                    this.savedState.switchToUnsavedState();
                    this.readingCrossTrackerReport.duplicateFromReport(this.writingCrossTrackerReport);
                    this.$refs.reading_mode.showActions();
                }

                this.$refs.artifact_table.refreshArtifactList();
                this.reading_mode = true;
            },

            hideFeedbacks() {
                this.successDisplayer.hideSuccess();
                this.errorDisplayer.hideError();
            },

            async showRestError(rest_error) {
                const error_details = await rest_error.response.json();
                if ('i18n_error_message' in error_details.error) {
                    this.error_message = error_details.error.i18n_error_message;
                }
            },

            reportCancelled() {
                this.hideFeedbacks();
                this.switchToReadingMode({saved_state: true});
            },

        },
        mounted() {
            this.readingController.init();
        }
    };
</script>)
