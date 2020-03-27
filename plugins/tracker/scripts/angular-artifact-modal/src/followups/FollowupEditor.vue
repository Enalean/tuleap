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
    <div class="artifact-modal-followups-add-form">
        <format-selector
            id="followup_comment"
            v-bind:label="label"
            v-bind:disabled="false"
            v-bind:required="false"
            v-model="format"
        />
        <rich-text-editor
            id="followup_comment"
            v-bind:format="format"
            v-bind:disabled="false"
            v-bind:required="false"
            rows="3"
            v-on:upload-image="reemit"
            v-model="content"
        />
    </div>
</template>
<script>
import FormatSelector from "../common/FormatSelector.vue";
import RichTextEditor from "../common/RichTextEditor.vue";
export default {
    name: "FollowupEditor",
    components: { RichTextEditor, FormatSelector },
    props: {
        value: Object,
    },
    computed: {
        label() {
            return this.$gettext("Comment");
        },
        content: {
            get() {
                return this.value.body;
            },
            set(new_content) {
                this.$emit("input", { format: this.format, body: new_content });
            },
        },
        format: {
            get() {
                return this.value.format;
            },
            set(new_format) {
                this.$emit("input", { format: new_format, body: this.content });
            },
        },
    },
    methods: {
        reemit(...args) {
            this.$emit("upload-image", ...args);
        },
    },
};
</script>
