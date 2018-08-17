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
                <option value="@status">{{ status_semantic_label }}</option>
                <option value="@submitted_on">{{ submitted_on_label }}</option>
                <option value="@last_update_date">{{ lud_label }}</option>
                <option value="@submitted_by">{{ submitted_by_label }}</option>
                <option value="@last_update_by">{{ luby_label }}</option>
                <option value="@assigned_to">{{ assigned_to_label }}</option>
            </select>
        </div>
    </div>
</template>)
(<script>
import { gettext_provider } from "../gettext-provider.js";
import {
    TQL_cross_tracker_autocomplete_keywords,
    TQL_cross_tracker_mode_definition
} from "./tql-configuration.js";
import { insertAllowedFieldInCodeMirror } from "plugin-tracker-TQL/allowed-field-inserter.js";
import { initializeTQLMode, codeMirrorify } from "plugin-tracker-TQL/builder.js";

export default {
    name: "QueryEditor",
    props: {
        writingCrossTrackerReport: Object
    },
    data() {
        return {
            code_mirror_instance: null
        };
    },
    computed: {
        query_label: () => gettext_provider.gettext("Query"),
        allowed_fields_label: () => gettext_provider.gettext("Allowed fields"),
        title_semantic_label: () => gettext_provider.gettext("Title"),
        description_semantic_label: () => gettext_provider.gettext("Description"),
        status_semantic_label: () => gettext_provider.gettext("Status"),
        submitted_on_label: () => gettext_provider.gettext("Submitted on"),
        lud_label: () => gettext_provider.gettext("Last update date"),
        submitted_by_label: () => gettext_provider.gettext("Submitted by"),
        luby_label: () => gettext_provider.gettext("Last update by"),
        assigned_to_label: () => gettext_provider.gettext("Assigned to"),
        placeholder: () => gettext_provider.gettext("Example: @title = 'value'"),
        tql_tips: () =>
            gettext_provider.gettext(
                "You can use: AND, OR, parenthesis. Autocomplete is activated with Ctrl + Space."
            )
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

        this.code_mirror_instance.on("change", () => {
            this.writingCrossTrackerReport.expert_query = this.code_mirror_instance.getValue();
        });
    },
    methods: {
        insertSelectedField(event) {
            insertAllowedFieldInCodeMirror(event, this.code_mirror_instance);
        },
        search() {
            this.$emit("triggerSearch");
        }
    }
};
</script>)
