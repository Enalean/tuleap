(<template>
    <reading-mode
        v-bind:backend-cross-tracker-report="backendCrossTrackerReport"
        v-bind:reading-cross-tracker-report="readingCrossTrackerReport"
        v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
        v-bind:report-mode="reportMode"
    ></reading-mode>
</template>)
(<script>
    import ReadingMode          from './reading-mode/ReadingMode.vue';
    import { gettext_provider } from './gettext-provider.js';

    export default {
        components: { ReadingMode },
        name: 'CrossTrackerWidget',
        props: [
            'backendCrossTrackerReport',
            'readingCrossTrackerReport',
            'writingCrossTrackerReport',
            'reportMode'
        ]
    };
</script>)
