(<template>
    <div class="cross-tracker-expert-content">
        <div class="cross-tracker-expert-content-query tlp-form-element">
            <label class="tlp-label" for="expert-query-textarea">{{ query_label }}</label>
            <textarea
                ref="query_textarea"
                type="text"
                class="cross-tracker-expert-content-query-textarea tlp-textarea"
                name="expert_query"
                id="expert-query-textarea"
                v-bind:placeholder="placeholder"
            >{{ writingCrossTrackerReport.expert_query }}</textarea>
            <p class="tlp-text-muted"><i class="fa fa-info-circle"></i> {{ tql_tips }}</p>
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="expert-query-allowed-fields">{{ allowed_fields_label }}</label>
            <select
                class="cross-tracker-expert-content-query-allowed-fields tlp-select"
                name="allowed-fields"
                id="expert-query-allowed-fields"
                multiple
                v-on:click.prevent="insertSelectedField"
            >
                <option value="@title">{{ title_semantic_label }}</option>
                <option value="@description">{{ description_semantic_label }}</option>
            </select>
        </div>
    </div>
</template>)
(<script>
    import { gettext_provider } from '../gettext-provider.js';
    import {
        TQL_cross_tracker_autocomplete_keywords,
        TQL_cross_tracker_mode_definition
    } from './tql-configuration.js';
    import {
        insertAllowedFieldInCodeMirror
    } from 'plugin-tracker-TQL/allowed-field-inserter.js';
    import {
        initializeTQLMode,
        codeMirrorify
    } from 'plugin-tracker-TQL/builder.js';

    export default {
        name: 'QueryEditor',
        props: [
            'writingCrossTrackerReport'
        ],
        data() {
            return {
                code_mirror_instance: null
            };
        },
        computed: {
            query_label               : () => gettext_provider.gettext("Query"),
            allowed_fields_label      : () => gettext_provider.gettext("Allowed fields"),
            title_semantic_label      : () => gettext_provider.gettext("Title"),
            description_semantic_label: () => gettext_provider.gettext("Description"),
            placeholder               : () => gettext_provider.gettext("Example: @title = 'value'"),
            tql_tips                  : () => gettext_provider.gettext("You can use: AND, OR, parenthesis. Autocomplete is activated with Ctrl + Space."),
        },
        methods: {
            insertSelectedField(event) {
                insertAllowedFieldInCodeMirror(event, this.code_mirror_instance);
            },
            refresh() {
                window.setTimeout(() => {
                    this.code_mirror_instance.refresh();
                }, 0);
            },
            search() {
                this.$emit('triggerSearch');
            }
        },
        created() {
            initializeTQLMode(TQL_cross_tracker_mode_definition);
        },
        mounted() {
            const submitFormCallback = () => {
                this.search();
            };

            this.code_mirror_instance = codeMirrorify({
                textarea_element: this.$refs.query_textarea,
                autocomplete_keywords: TQL_cross_tracker_autocomplete_keywords,
                submitFormCallback
            });

            this.code_mirror_instance.on('change', () => {
                this.writingCrossTrackerReport.expert_query = this.code_mirror_instance.getValue();
            });
        }
    };
</script>)
