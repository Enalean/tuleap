(<template>
    <div class="cross-tracker-writing-mode">
        <div class="dashboard-widget-content-cross-tracker-form">
            <div class="dashboard-widget-content-cross-tracker-form-projects tlp-form-element">
                <label class="tlp-label" for="project">{{ project_label }} <i class="fa fa-asterisk"></i></label>
                <select class="dashboard-widget-content-cross-tracker-form-projects-input tlp-select" id="project" name="project">
                </select>
            </div>
            <div class="dashboard-widget-content-cross-tracker-form-trackers tlp-form-element">
                <label class="tlp-label" for="tracker">{{ tracker_label }} <i class="fa fa-asterisk"></i></label>
                <div class="tlp-form-element tlp-form-element-append">
                    <select class="dashboard-widget-content-cross-tracker-form-trackers-input tlp-select" id="tracker" name="tracker">
                    </select>
                    <button type="button"
                            class="dashboard-widget-content-cross-tracker-form-trackers-add tlp-append tlp-append tlp-button-primary tlp-button-outline tlp-button"
                    >
                        <i class="cross-tracker-loader tlp-button-icon fa fa-spinner fa-spin"></i>
                        <i class="cross-tracker-add-icon fa fa-plus tlp-button-icon"></i> {{ add_button_label }}
                    </button>
                </div>
            </div>
        </div>
        <div class="dashboard-widget-content-cross-tracker-form-trackers-selected"></div>
        <query-editor
            v-bind:writing-cross-tracker-report="writingCrossTrackerReport"
        ></query-editor>
        <div class="writing-mode-actions">
            <button
                class="tlp-button-primary tlp-button-outline writing-mode-actions-cancel"
                v-on:click="cancel"
            >{{ cancel_label }}</button>
            <button
                class="tlp-button-primary writing-mode-actions-search"
                v-on:click="search"
            >{{ search_label }}</button>
        </div>
    </div>
</template>)
(<script>
    import { gettext_provider } from '../gettext-provider.js';
    import QueryEditor          from './QueryEditor.vue';

    export default {
        components: { QueryEditor },
        name: 'WritingMode',
        props: [
            'writingCrossTrackerReport',
        ],
        computed: {
            project_label   : () => gettext_provider.gettext("Project"),
            tracker_label   : () => gettext_provider.gettext("Tracker"),
            search_label    : () => gettext_provider.gettext("Search"),
            cancel_label    : () => gettext_provider.gettext("Cancel"),
            add_button_label: () => gettext_provider.gettext("Add"),
        },
        methods: {
            cancel() {
                this.$emit('switchToReadingMode', { saved_state: true});
            },
            search() {
                this.$emit('switchToReadingMode', { saved_state: false });
            }
        }
    }
</script>)
