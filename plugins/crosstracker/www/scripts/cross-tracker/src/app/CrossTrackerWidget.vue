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
            ref="writing_mode"
            v-show="! reading_mode"
            v-bind:backend-cross-tracker-report="backendCrossTrackerReport"
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
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
            'projectSelector',
            'trackerSelectionController',
            'readingController',
            'trackerSelector',
            'trackerSelectionLoader'
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

                this.projectSelector.loadProjectsOnce();
                this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
                this.hideFeedbacks();
                this.reading_mode = false;
                this.$refs.writing_mode.refresh();
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
            this.projectSelector.init();
            this.trackerSelectionController.init();
            this.trackerSelector.init();
            this.readingController.init();
            this.trackerSelectionLoader.init();
        }
    };
</script>)
