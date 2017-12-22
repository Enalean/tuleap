(<template>
    <div class="cross-tracker-reading-mode">
        <div class="reading-mode-report"
            v-bind:class="{'disabled' : is_switch_disabled}"
            v-on:click="switchToWritingMode"
        >
            <tracker-list-reading-mode
                v-bind:reading-cross-tracker-report="readingCrossTrackerReport"
            ></tracker-list-reading-mode>
            <div class="reading-mode-query"
                v-if="is_expert_query_not_empty"
            >{{ readingCrossTrackerReport.expert_query }}</div>
        </div>
        <div class="dashboard-widget-content-cross-tracker-reading-mode-actions"
            v-bind:class="{'cross-tracker-hide': are_actions_hidden }"
        >
            <button class="tlp-button-primary tlp-button-outline dashboard-widget-content-cross-tracker-reading-mode-actions-cancel">{{ cancel }}</button>
            <button class="tlp-button-primary dashboard-widget-content-cross-tracker-reading-mode-actions-save">
                <span class="cross-tracker-loader"><i class="tlp-button-icon fa fa-spinner fa-spin"></i></span>
                {{ save_report }}
            </button>
        </div>
    </div>
</template>)
(<script>
    import TrackerListReadingMode from './TrackerListReadingMode.vue';
    import { gettext_provider }   from '../gettext-provider.js';
    import { isAnonymous }        from '../user-service.js';

    export default {
        components: { TrackerListReadingMode } ,
        props: [
            'backendCrossTrackerReport',
            'readingCrossTrackerReport'
        ],
        data() {
            return {
                are_actions_hidden: true
            };
        },
        computed: {
            save_report:() => gettext_provider.gettext("Save report"),
            cancel:()      => gettext_provider.gettext("Cancel"),
            is_switch_disabled() {
                return (! this.backendCrossTrackerReport.loaded || this.is_user_anonymous);
            },
            is_user_anonymous() {
                return isAnonymous();
            },
            is_expert_query_not_empty() {
                return this.readingCrossTrackerReport.expert_query !== '';
            }
        },
        methods: {
            switchToWritingMode() {
                if (this.is_user_anonymous) {
                    return;
                }

                this.$emit('switchToWritingMode');
            },
            showActions() {
                this.are_actions_hidden = false;
            },
            hideActions() {
                this.are_actions_hidden = true;
            }
        },
    };
</script>)
