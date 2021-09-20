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
        <!-- eslint-disable-next-line vue/html-self-closing -->
        <tuleap-artifact-modal-format-selector
            v-bind:identifier.prop="'followup_comment'"
            v-bind:label.prop="label"
            v-bind:value.prop="format"
            v-bind:isInPreviewMode.prop="is_in_preview_mode"
            v-bind:isPreviewLoading.prop="is_preview_loading"
            v-on:interpret-content-event="togglePreview"
        ></tuleap-artifact-modal-format-selector>
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
import RichTextEditor from "../common/RichTextEditor.vue";
import { getCommentLabel } from "../gettext-catalog";
import { textfield_mixin } from "../common/textfield-mixin.js";

export default {
    name: "FollowupEditor",
    components: { RichTextEditor },
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
