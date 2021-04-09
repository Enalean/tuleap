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
    <div class="artifact-modal-text-label-with-format">
        <label class="tlp-label artifact-modal-text-label" v-bind:for="id">
            {{ label }}
            <i v-if="required" class="fa fa-asterisk artifact-modal-text-asterisk"></i>
        </label>
        <div class="artifact-modal-text-label-with-format-and-helper-container">
            <select
                v-bind:id="selectbox_id"
                v-model="format"
                v-bind:disabled="disabled_format_selectbox"
                class="tlp-select tlp-select-small tlp-select-adjusted"
                data-test="format"
            >
                <option v-bind:value="text_format" v-if="is_text_format_option_enabled">
                    {{ text_label }}
                </option>
                <option v-bind:value="html_format">{{ html_label }}</option>
                <option v-bind:value="commonmark_format">{{ commonmark_label }}</option>
            </select>
            <commonmark-preview-button
                v-if="is_commonmark_format"
                v-bind:is_in_preview_mode="is_in_preview_mode"
                v-bind:is_preview_loading="is_preview_loading"
                v-on:commonmark-preview-event="$emit('interpret-content-event')"
            />
            <commonmark-syntax-helper
                v-if="is_commonmark_format"
                v-bind:disabled="is_syntax_helper_button_disabled"
            />
        </div>
    </div>
</template>
<script>
import {
    isValidTextFormat,
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "../../../../constants/fields-constants.js";
import CommonmarkSyntaxHelper from "./CommonmarkSyntaxHelper.vue";
import { getCommonMarkLabel, getHTMLLabel, getTextLabel } from "../gettext-catalog";
import CommonmarkPreviewButton from "./CommonmarkPreviewButton.vue";

export default {
    name: "FormatSelector",
    components: { CommonmarkPreviewButton, CommonmarkSyntaxHelper },
    props: {
        id: String,
        label: String,
        value: {
            type: String,
            validator(value) {
                return isValidTextFormat(value);
            },
        },
        disabled: Boolean,
        required: Boolean,
        is_in_preview_mode: Boolean,
        is_preview_loading: Boolean,
        is_text_format_option_enabled: Boolean,
    },
    computed: {
        disabled_format_selectbox() {
            return this.disabled || this.is_in_preview_mode || this.is_preview_loading;
        },
        format: {
            get() {
                return this.value;
            },
            set(new_format) {
                this.$emit("input", new_format);
            },
        },
        selectbox_id() {
            return "format_" + this.id;
        },
        is_commonmark_format() {
            return this.value === TEXT_FORMAT_COMMONMARK;
        },
        text_format() {
            return TEXT_FORMAT_TEXT;
        },
        text_label() {
            return getTextLabel();
        },
        html_format() {
            return TEXT_FORMAT_HTML;
        },
        html_label() {
            return getHTMLLabel();
        },
        commonmark_format() {
            return TEXT_FORMAT_COMMONMARK;
        },
        commonmark_label() {
            return getCommonMarkLabel();
        },
        is_syntax_helper_button_disabled() {
            return this.is_in_preview_mode || this.is_preview_loading;
        },
    },
};
</script>
