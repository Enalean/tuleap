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
    <div
        class="tlp-form-element"
        v-bind:class="{
            'tlp-form-element-disabled': disabled,
            'tlp-form-element-error': is_required_and_empty,
        }"
        data-test="text-field"
    >
        <!-- eslint-disable-next-line vue/html-self-closing -->
        <tuleap-artifact-modal-format-selector
            v-bind:identifier.prop="id"
            v-bind:label.prop="field.label"
            v-bind:disabled.prop="disabled"
            v-bind:required.prop="field.required"
            v-bind:value.prop="format"
            v-bind:isInPreviewMode.prop="is_in_preview_mode"
            v-bind:isPreviewLoading.prop="is_preview_loading"
            v-on:interpret-content-event="togglePreview"
            data-test="format-selector"
        ></tuleap-artifact-modal-format-selector>
        <rich-text-editor
            v-bind:id="id"
            v-bind:format="format"
            v-bind:disabled="disabled"
            v-bind:required="field.required"
            rows="5"
            v-model="content"
            v-on:upload-image="reemit"
            v-on:format-change="onFormatChange"
            v-show="!is_in_preview_mode && !is_in_error"
        />
        <div
            v-if="is_in_preview_mode && !is_in_error"
            v-dompurify-html="interpreted_commonmark"
            data-test="text-field-commonmark-preview"
        ></div>
        <div v-if="is_in_error" class="tlp-alert-danger" data-test="text-field-error">
            {{ error_introduction }}{{ error_text }}
        </div>
    </div>
</template>
<script>
import RichTextEditor from "../../common/RichTextEditor.vue";
import { isDisabled } from "../disabled-field-detector.js";
import { textfield_mixin } from "../../common/textfield-mixin.js";

export default {
    name: "TextField",
    components: { RichTextEditor },
    mixins: [textfield_mixin],
    props: {
        field: {
            type: Object,
            default: () => ({}),
        },
    },
    computed: {
        disabled() {
            return isDisabled(this.field) || this.is_preview_loading;
        },
        content: {
            get() {
                return this.value.content;
            },
            set(new_content) {
                this.$emit("input", { format: this.format, content: new_content });
            },
        },
        id() {
            return "tracker_field_" + this.field.field_id;
        },
        is_required_and_empty() {
            return this.field.required && this.content === "";
        },
    },
    mounted() {
        this.initial_text_field_format = this.format;
    },
    methods: {
        onFormatChange(new_format, new_content) {
            this.$emit("input", { format: new_format, content: new_content });
        },
        togglePreview() {
            this.interpretCommonMark(this.content);
        },
    },
};
</script>
