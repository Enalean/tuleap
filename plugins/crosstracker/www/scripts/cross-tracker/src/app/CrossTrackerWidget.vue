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
        <reading-mode
            ref="reading_mode"
            v-show="reading_mode"
            v-bind:backend-cross-tracker-report="backendCrossTrackerReport"
            v-bind:reading-cross-tracker-report="readingCrossTrackerReport"
            v-bind:reading-controller="readingController"
            v-on:switchToWritingMode="switchToWritingMode"
        ></reading-mode>
        <writing-mode
            v-if="! reading_mode"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
            v-bind:error-displayer="errorDisplayer"
            v-on:switchToReadingMode="switchToReadingMode"
        ></writing-mode>
        <artifact-table-renderer
            v-bind:query-result-controller="queryResultController"
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
            'queryResultController',
            'readingController',
        ],
        data() {
            return {
                reading_mode: true
            };
        },
        computed: {
            is_user_anonymous() {
                return isAnonymous();
            }
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
                    this.queryResultController.loadFirstBatchOfArtifacts();
                    this.readingCrossTrackerReport.duplicateFromReport(this.writingCrossTrackerReport);
                    this.$refs.reading_mode.showActions();
                }

                this.reading_mode = true;
            },
            hideFeedbacks() {
                this.successDisplayer.hideSuccess();
                this.errorDisplayer.hideError();
            }
        },
        mounted() {
            this.readingController.init();
        }
    };
</script>)
