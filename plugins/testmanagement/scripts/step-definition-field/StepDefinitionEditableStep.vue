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
        <step-definition-actions v-bind:value="step.description_format" v-on:input="toggleRTE">
            <step-deletion-action-button-mark-as-deleted
                v-bind:mark-as-deleted="markAsDeleted"
                v-bind:is_deletion="true"
            />
        </step-definition-actions>
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
            rows="3"
            v-model="step.raw_description"
        ></textarea>
        <div
            class="muted tracker-richtexteditor-help"
            v-bind:class="{ shown: is_current_step_in_html_format }"
            v-bind:id="'field_description_' + step.uuid + '_' + field_id + '-help'"
        ></div>

        <section class="ttm-definition-step-expected">
            <step-definition-arrow-expected />
            <div class="ttm-definition-step-expected-edit">
                <div class="ttm-definition-step-expected-edit-title">
                    <translate>Expected results</translate>
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
                    v-model="step.raw_expected_results"
                ></textarea>
                <div
                    class="muted tracker-richtexteditor-help"
                    v-bind:class="{ shown: is_current_step_in_html_format }"
                    v-bind:id="'field_expected_results_' + step.uuid + '_' + field_id + '-help'"
                ></div>
            </div>
        </section>
    </div>
</template>

<script>
import StepDeletionActionButtonMarkAsDeleted from "./StepDeletionActionButtonMarkAsDeleted.vue";
import StepDefinitionArrowExpected from "./StepDefinitionArrowExpected.vue";
import StepDefinitionActions from "./StepDefinitionActions.vue";
import { mapState, mapGetters } from "vuex";
import { RTE } from "codendi";

export default {
    name: "StepDefinitionEditableStep",
    components: {
        StepDefinitionArrowExpected,
        StepDefinitionActions,
        StepDeletionActionButtonMarkAsDeleted,
    },
    props: {
        step: Object,
    },
    computed: {
        ...mapState([
            "field_id",
            "is_dragging",
            "upload_url",
            "upload_field_name",
            "upload_max_size",
        ]),
        ...mapGetters(["is_text"]),
        description_id() {
            return "field_description_" + this.step.uuid + "_" + this.field_id;
        },
        expected_results_id() {
            return "field_expected_results_" + this.step.uuid + "_" + this.field_id;
        },
        description_help_id() {
            return this.description_id + "-help";
        },

        expected_results_help_id() {
            return this.expected_results_id + "-help";
        },
        is_current_step_in_html_format() {
            return !this.is_text(this.step.description_format);
        },
    },
    watch: {
        is_dragging(new_value) {
            if (new_value === false) {
                this.loadEditor();
            } else {
                this.getEditorsContent();
            }
        },
    },
    mounted() {
        this.loadEditor();
        this.$emit("removeDeletedStepsOnFormSubmission", this.$refs.description.form);
    },
    methods: {
        markAsDeleted() {
            this.$emit("markAsDeleted");
        },
        getEditorsContent() {
            if (!this.is_text(this.step.description_format)) {
                this.step.raw_description = this.editors[1].getContent();
                this.step.raw_expected_results = this.editors[0].getContent();
            }
        },
        toggleRTE(event, value) {
            this.step.description_format = value;

            for (const editor of this.editors) {
                editor.toggle(event, value);
            }
        },
        loadRTE(field) {
            const element = this.$refs[field];
            const is_html = this.is_current_step_in_html_format;
            const editor = new RTE(element, {
                toggle: true,
                default_in_html: false,
                id: element.id,
                htmlFormat: is_html,
                autoresize_when_ready: false,
            });

            if (is_html) {
                editor.init_rte();
            }

            return editor;
        },
        loadEditor() {
            this.editors = [this.loadRTE("expected_results"), this.loadRTE("description")];
        },
    },
};
</script>
