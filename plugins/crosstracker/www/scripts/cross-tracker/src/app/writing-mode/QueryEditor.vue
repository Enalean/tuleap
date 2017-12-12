(<template>
    <div class="cross-tracker-expert-content">
        <div class="cross-tracker-expert-content-query tlp-form-element">
            <label class="tlp-label" for="expert-query-textarea">{{ query_label }}</label>
            <textarea
                type="text"
                class="cross-tracker-expert-content-query-textarea tlp-textarea"
                name="expert_query"
                id="expert-query-textarea"
                v-bind:placeholder="placeholder"
                v-model.trim="writingCrossTrackerReport.expert_query"
            ></textarea>
            <p class="tlp-text-muted"><i class="fa fa-info-circle"></i> {{ tql_tips }}</p>
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="expert-query-allowed-fields">{{ allowed_fields_label }}</label>
            <select
                class="tlp-select"
                name="allowed-fields"
                id="expert-query-allowed-fields"
                multiple
            >
                <option value="@title">{{ title_semantic_label }}</option>
            </select>
        </div>
    </div>
</template>)
(<script>
    import { gettext_provider } from '../gettext-provider.js';

    export default {
        name: 'QueryEditor',
        props: [
            'writingCrossTrackerReport'
        ],
        computed: {
            query_label: () => gettext_provider.gettext("Query"),
            allowed_fields_label: () => gettext_provider.gettext("Allowed fields"),
            title_semantic_label: () => gettext_provider.gettext("Title (Semantic)"),
            placeholder: () => gettext_provider.gettext("Example: @title = 'value'"),
            tql_tips: () => gettext_provider.gettext("The query edition is under construction. The value you submit is not yet taken into account"),
        }
    };
</script>)
