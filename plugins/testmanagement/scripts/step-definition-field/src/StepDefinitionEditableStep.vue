<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div>
        <input
            type="hidden"
            v-bind:name="'artifact[' + field_id + '][id][]'"
            v-bind:value="step.id"
        />
        <step-definition-actions
            v-bind:step="step"
            v-bind:format_select_id="format_select_id"
            v-bind:is_in_preview_mode="is_in_preview_mode"
            v-bind:is_preview_loading="is_preview_loading"
            v-bind:disabled="false"
            v-on:input="onInputEmitToggleRte"
            v-on:interpret-content-event="togglePreview"
        />
        <input
            type="hidden"
            v-bind:name="'artifact[' + field_id + '][description_format][]'"
            v-bind:value="step.description_format"
        />
        <textarea
            ref="description"
            class="ttm-definition-step-description-textarea"
            v-bind:id="description_id"
            v-bind:name="'artifact[' + field_id + '][description][]'"
            v-bind:data-help-id="description_help_id"
            v-bind:data-upload-url="upload_url"
            v-bind:data-upload-field-name="upload_field_name"
            v-bind:data-upload-max-size="upload_max_size"
            data-test="description-textarea"
            rows="3"
            v-bind:value="raw_description"
            v-on:input="updateDescription"
            v-show="is_in_edit_mode_without_error"
            v-bind:disabled="is_preview_loading"
        ></textarea>
        <div
            v-if="is_in_preview_mode && !is_preview_in_error"
            v-dompurify-html="interpreted_description"
            data-test="description-preview"
        ></div>
        <div class="alert alert-error" v-if="is_preview_in_error" data-test="description-error">
            {{ $gettext("There was an error in the Markdown preview:") }}
            {{ error_text }}
        </div>
        <p class="text-info tracker-richtexteditor-help shown" v-bind:id="description_help_id"></p>

        <section class="ttm-definition-step-expected">
            <step-definition-arrow-expected />
            <div class="ttm-definition-step-expected-edit">
                <div class="ttm-definition-step-expected-edit-title">
                    {{ $gettext("Expected results") }}
                </div>

                <input
                    type="hidden"
                    v-bind:name="'artifact[' + field_id + '][expected_results_format][]'"
                    v-bind:value="step.description_format"
                />
                <textarea
                    ref="expected_results"
                    class="ttm-definition-step-expected-results-textarea"
                    v-bind:id="expected_results_id"
                    v-bind:name="'artifact[' + field_id + '][expected_results][]'"
                    v-bind:data-help-id="expected_results_help_id"
                    v-bind:data-upload-url="upload_url"
                    v-bind:data-upload-field-name="upload_field_name"
                    v-bind:data-upload-max-size="upload_max_size"
                    rows="3"
                    v-bind:value="raw_expected_results"
                    v-on:input="updateExpectedResults"
                    v-show="is_in_edit_mode_without_error"
                    data-test="expected-results-textarea"
                    v-bind:disabled="is_preview_loading"
                ></textarea>
                <div
                    v-if="is_in_preview_mode"
                    v-dompurify-html="interpreted_expected_result"
                    data-test="expected-results-preview"
                ></div>
                <div
                    class="alert alert-error"
                    v-if="is_preview_in_error"
                    data-test="expected-results-error"
                >
                    {{ $gettext("There was an error in the Markdown preview:") }}
                    {{ error_text }}
                </div>
                <div
                    class="muted tracker-richtexteditor-help shown"
                    v-bind:id="expected_results_help_id"
                ></div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import type CKEDITOR from "ckeditor4";
import { ref, computed, onMounted, watch, onUnmounted } from "vue";
import { useState } from "vuex-composition-helpers";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import { strictInject } from "@tuleap/vue-strict-inject";
import StepDefinitionArrowExpected from "./StepDefinitionArrowExpected.vue";
import StepDefinitionActions from "./StepDefinitionActions.vue";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import {
    getUploadImageOptions,
    UploadImageFormFactory,
} from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import { TEXT_FORMAT_HTML } from "@tuleap/plugin-tracker-constants";
import { postInterpretCommonMark } from "./api/rest-querier";
import type { Step } from "./Step";
import type { TextEditorInterface } from "@tuleap/plugin-tracker-rich-text-editor";
import { PROJECT_ID, FIELD_ID } from "./injection-keys";

const project_id = strictInject(PROJECT_ID);
const field_id = strictInject(FIELD_ID);

const { is_dragging, upload_url, upload_field_name, upload_max_size } = useState([
    "is_dragging",
    "upload_url",
    "upload_field_name",
    "upload_max_size",
]);

const props = defineProps<{
    step: Step;
}>();

const emit = defineEmits<{
    (e: "update-description", new_description: string): void;
    (e: "update-expected-results", new_expected_result: string): void;
    (e: "toggle-rte", new_format: TextFieldFormat): void;
}>();

const description = ref<HTMLTextAreaElement>();
const expected_results = ref<HTMLTextAreaElement>();

const interpreted_description = ref("");
const interpreted_expected_result = ref("");
const is_in_preview_mode = ref(false);
const is_preview_loading = ref(false);
const is_preview_in_error = ref(false);
const error_text = ref("");
const editors = ref<Array<TextEditorInterface>>([]);
const raw_description = ref(props.step.raw_description);
const raw_expected_results = ref(props.step.raw_expected_results);

const description_id = computed(() => "field_description_" + props.step.uuid + "_" + field_id);
const expected_results_id = computed(
    () => "field_expected_results_" + props.step.uuid + "_" + field_id,
);
const description_help_id = computed(() => description_id.value + "-help");
const expected_results_help_id = computed(() => expected_results_id.value + "-help");
const is_current_step_in_html_format = computed(
    () => props.step.description_format === TEXT_FORMAT_HTML,
);
const format_select_id = computed(() => description_id.value + "-help");
const is_in_edit_mode_without_error = computed(
    () => !is_in_preview_mode.value && !is_preview_in_error.value,
);

watch(
    () => is_dragging.value,
    (new_value) => {
        if (new_value === false) {
            loadEditor();
        } else {
            getEditorsContent();
        }
    },
);

onMounted(() => {
    loadEditor();
});

onUnmounted(() => {
    editors.value[0]?.destroy();
    editors.value[1]?.destroy();
});

function getEditorsContent() {
    if (is_current_step_in_html_format.value && areRTEEditorsSet()) {
        raw_description.value = editors.value[1].getContent();
        raw_expected_results.value = editors.value[0].getContent();
    }
}

function areRTEEditorsSet(): boolean {
    return editors.value[0] !== undefined && editors.value[1] !== undefined;
}

function onInputEmitToggleRte(new_format: TextFieldFormat) {
    emit("toggle-rte", new_format);
}

function loadRTE(textarea_element: HTMLTextAreaElement) {
    const text_area = textarea_element;
    let locale = "en_US";
    if (document.body.dataset.userLocale) {
        locale = document.body.dataset.userLocale;
    }
    const image_upload_factory = UploadImageFormFactory(document, locale);
    const help_block = image_upload_factory.createHelpBlock(text_area);
    const editor = RichTextEditorFactory.forFlamingParrotWithExistingFormatSelector(
        document,
        locale,
    );

    const options = {
        format_selectbox_id: format_select_id.value,
        format_selectbox_value: props.step.description_format,
        getAdditionalOptions: (textarea: HTMLTextAreaElement) => getUploadImageOptions(textarea),
        onFormatChange: (new_format: TextFieldFormat) => {
            if (help_block) {
                help_block.onFormatChange(new_format);
            }
            getEditorsContent();
        },
        onEditorInit: (ckeditor: CKEDITOR.editor, textarea: HTMLTextAreaElement) =>
            image_upload_factory.initiateImageUpload(ckeditor, textarea),
    };
    return editor.createRichTextEditor(text_area, options);
}

function loadEditor() {
    if (!expected_results.value || !description.value) {
        return;
    }

    editors.value = [loadRTE(expected_results.value), loadRTE(description.value)];
}

function updateDescription(event: Event) {
    if (!(event.target instanceof HTMLTextAreaElement)) {
        return;
    }

    raw_description.value = event.target.value;
    emit("update-description", event.target.value);
}

function updateExpectedResults(event: Event) {
    if (!(event.target instanceof HTMLTextAreaElement)) {
        return;
    }

    raw_expected_results.value = event.target.value;
    emit("update-expected-results", event.target.value);
}

function togglePreview() {
    is_preview_in_error.value = false;
    error_text.value = "";

    if (is_in_preview_mode.value) {
        is_in_preview_mode.value = false;
        return Promise.resolve();
    }

    is_preview_loading.value = true;

    return Promise.all([
        postInterpretCommonMark(project_id, props.step.raw_description),
        postInterpretCommonMark(project_id, props.step.raw_expected_results),
    ])
        .then((interpreted_fields: Array<string>) => {
            interpreted_description.value = interpreted_fields[0];
            interpreted_expected_result.value = interpreted_fields[1];
        })
        .catch((error) => {
            is_preview_in_error.value = true;
            error_text.value = error;
        })
        .finally(() => {
            is_preview_loading.value = false;
            is_in_preview_mode.value = !is_in_preview_mode.value;
        });
}
</script>
