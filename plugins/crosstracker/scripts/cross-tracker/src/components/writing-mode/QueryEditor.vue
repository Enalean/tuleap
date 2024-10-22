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
        <div class="cross-tracker-expert-content-query tlp-form-element">
            <label class="tlp-label" for="expert-query-textarea">{{ $gettext("Query") }}</label
            ><textarea
                ref="query_textarea"
                type="text"
                class="cross-tracker-expert-content-query-textarea tlp-textarea"
                name="expert_query"
                id="expert-query-textarea"
                v-bind:placeholder="$gettext(`Example: @title = 'value'`)"
                v-model="value"
                data-test="expert-query-textarea"
            ></textarea>
            <p class="tlp-text-info">
                <i
                    aria-hidden="true"
                    class="cross-tracker-query-editor-info-icon fa-solid fa-info-circle"
                ></i
                >{{
                    $gettext(
                        "You can use: AND, OR, WITH PARENT, WITHOUT PARENT, WITH CHILDREN, WITHOUT CHILDREN, BETWEEN(), NOW(), MYSELF(), OPEN(), IN(), NOT IN(), parenthesis. Autocomplete is activated with Ctrl + Space.",
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
    TQL_cross_tracker_mode_definition,
} from "../../helpers/tql-configuration";
import type { TQLCodeMirrorEditor } from "@tuleap/plugin-tracker-tql-codemirror";
import {
    codeMirrorify,
    initializeTQLMode,
    insertAllowedFieldInCodeMirror,
} from "@tuleap/plugin-tracker-tql-codemirror";
import type { WritingCrossTrackerReport } from "../../domain/WritingCrossTrackerReport";

const { $gettext } = useGettext();

const props = defineProps<{ writing_cross_tracker_report: WritingCrossTrackerReport }>();
const emit = defineEmits<{ (e: "trigger-search"): void }>();

const code_mirror_instance = ref<TQLCodeMirrorEditor | null>(null);

const value = ref<string>(props.writing_cross_tracker_report.expert_query);

const query_textarea = ref<InstanceType<typeof HTMLTextAreaElement>>();

initializeTQLMode(TQL_cross_tracker_mode_definition);

onMounted(() => {
    const submitFormCallback = (): void => {
        emit("trigger-search");
    };

    if (!(query_textarea.value instanceof HTMLTextAreaElement)) {
        throw new Error("Textarea not found in DOM");
    }

    code_mirror_instance.value = codeMirrorify(
        query_textarea.value,
        TQL_cross_tracker_autocomplete_keywords,
        submitFormCallback,
    );

    if (!code_mirror_instance.value) {
        throw new Error("Code mirror is not accessible");
    }
    code_mirror_instance.value.on("change", () => {
        if (!code_mirror_instance.value) {
            throw new Error("Code mirror is not accessible");
        }
        props.writing_cross_tracker_report.setExpertQuery(code_mirror_instance.value.getValue());
    });
});

function insertSelectedField(event: Event): void {
    if (!code_mirror_instance.value) {
        throw new Error("Code mirror is not accessible for adding field");
    }
    insertAllowedFieldInCodeMirror(event, code_mirror_instance.value);
}

defineExpose({
    value,
    code_mirror_instance,
});
</script>
