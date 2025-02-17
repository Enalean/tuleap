<!--
  - Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="cross-tracker-expert-content">
        <div class="cross-tracker-expert-content-query tlp-form-element" data-test="expert-query">
            <label class="tlp-label" ref="query_label">{{ $gettext("Query") }}</label>
            <p class="tlp-text-info">
                <i
                    aria-hidden="true"
                    class="cross-tracker-query-editor-info-icon fa-solid fa-info-circle"
                ></i
                >{{
                    $gettext(
                        "You can use: AND, OR, WITH PARENT, WITHOUT PARENT, WITH CHILDREN, WITHOUT CHILDREN, BETWEEN(), NOW(), MYSELF(), OPEN(), IN(), NOT IN(), MY_PROJECTS(), parenthesis. Autocomplete is activated with Ctrl + Space.",
                    )
                }}
            </p>
        </div>
        <div class="tlp-form-element">
            <label
                class="cross-tracker-expert-content-fields-label tlp-label"
                for="expert-query-allowed-fields"
                >{{ $gettext("Semantic suggestions") }}</label
            ><select
                class="cross-tracker-expert-content-query-allowed-fields tlp-select"
                name="allowed-fields"
                id="expert-query-allowed-fields"
                multiple
                v-on:click.prevent="insertSelectedField"
            >
                <option value="@id">{{ $gettext("Artifact id") }}</option>
                <option value="@title">{{ $gettext("Title") }}</option>
                <option value="@description">{{ $gettext("Description") }}</option>
                <option value="@status">{{ $gettext("Status") }}</option>
                <option value="@submitted_on">{{ $gettext("Submitted on") }}</option>
                <option value="@last_update_date">{{ $gettext("Last update date") }}</option>
                <option value="@submitted_by">{{ $gettext("Submitted by") }}</option>
                <option value="@last_update_by">{{ $gettext("Last update by") }}</option>
                <option value="@assigned_to">{{ $gettext("Assigned to") }}</option>
            </select>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import {
    TQL_cross_tracker_autocomplete_keywords,
    cross_tracker_allowed_keywords,
} from "../../helpers/tql-configuration";
import type { TQLCodeMirrorEditor } from "@tuleap/plugin-tracker-tql-codemirror";
import {
    buildParserDefinition,
    buildTQLEditor,
    insertAllowedFieldInCodeMirror,
} from "@tuleap/plugin-tracker-tql-codemirror";
import { Option } from "@tuleap/option";
import type { Query } from "../../type";

const { $gettext } = useGettext();

const props = defineProps<{ writing_query: Query }>();
const emit = defineEmits<{ (e: "trigger-search"): void }>();
const tql_query = ref<string>(props.writing_query.tql_query);

let code_mirror_instance: Option<TQLCodeMirrorEditor> = Option.nothing();

const query_label = ref<HTMLElement>();

onMounted(() => {
    tql_query.value = props.writing_query.tql_query;

    const submit_form_callback = (editor: TQLCodeMirrorEditor): void => {
        tql_query.value = editor.state.doc.toString();
        emit("trigger-search");
    };

    const update_callback = (editor: TQLCodeMirrorEditor): void => {
        tql_query.value = editor.state.doc.toString();
    };

    const editor = buildTQLEditor(
        {
            autocomplete: TQL_cross_tracker_autocomplete_keywords,
            parser_definition: buildParserDefinition(cross_tracker_allowed_keywords),
        },
        $gettext(
            `Example: SELECT @pretty_title FROM @project.name = 'my-project' WHERE @title = 'value'`,
        ),
        tql_query.value,
        submit_form_callback,
        update_callback,
    );
    code_mirror_instance = Option.fromValue(editor);
    query_label.value?.insertAdjacentElement("afterend", editor.dom);
    editor.focus();
});

function insertSelectedField(event: Event): void {
    code_mirror_instance.apply((editor) => {
        insertAllowedFieldInCodeMirror(event, editor);
    });
}

function clearEditor(): void {
    code_mirror_instance.apply((editor) => {
        editor.dispatch({
            changes: { from: 0, to: editor.state.doc.length, insert: "" },
        });
    });
}

defineExpose({ clearEditor, tql_query });
</script>
