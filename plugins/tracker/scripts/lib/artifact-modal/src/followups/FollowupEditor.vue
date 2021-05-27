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
    <div class="artifact-modal-followups-add-form" data-test="add-comment-form">
        <format-selector
            id="followup_comment"
            v-bind:label="label"
            v-bind:disabled="false"
            v-bind:required="false"
            v-bind:value="format"
            v-bind:is_in_preview_mode="is_in_preview_mode"
            v-bind:is_preview_loading="is_preview_loading"
            v-bind:is_text_format_option_enabled="false"
            v-on:interpret-content-event="togglePreview"
        />
        <rich-text-editor
            id="followup_comment"
            v-bind:format="format"
            v-bind:disabled="is_preview_loading"
            v-bind:required="false"
            rows="3"
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
import FormatSelector from "../common/FormatSelector.vue";
import RichTextEditor from "../common/RichTextEditor.vue";
import { getCommentLabel } from "../gettext-catalog";
import { textfield_mixin } from "../common/textfield-mixin.js";

export default {
    name: "FollowupEditor",
    components: { RichTextEditor, FormatSelector },
    mixins: [textfield_mixin],
    computed: {
        label() {
            return getCommentLabel();
        },
        content: {
            get() {
                return this.value.body;
            },
            set(new_content) {
                this.$emit("input", { format: this.format, body: new_content });
            },
        },
    },
    methods: {
        onFormatChange(new_format, new_content) {
            this.$emit("input", { format: new_format, body: new_content });
        },
        togglePreview() {
            this.interpretCommonMark(this.content);
        },
    },
};
</script>
