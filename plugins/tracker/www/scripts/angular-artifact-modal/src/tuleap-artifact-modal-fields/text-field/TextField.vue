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
            'tlp-form-element-error': is_required_and_empty
        }"
        data-test="form-element"
    >
        <div class="artifact-modal-text-label-with-format">
            <label class="tlp-label artifact-modal-text-label" v-bind:for="id">
                {{ field.label }}<i v-if="field.required" class="fa fa-asterisk artifact-modal-text-asterisk"></i>
            </label>
            <select
                v-model="format"
                v-bind:disabled="disabled"
                class="tlp-select tlp-select-small tlp-select-adjusted"
                data-test="format"
            >
                <option v-bind:value="text_format">Text</option>
                <option v-bind:value="html_format">HTML</option>
            </select>
        </div>
        <textarea
            v-bind:id="id"
            v-model="content"
            v-bind:required="field.required"
            v-bind:disabled="disabled"
            class="tlp-textarea"
            rows="5"
            ref="ckeditor_mount_point"
            data-test="textarea"
        ></textarea>
    </div>
</template>
<script>
import CKEDITOR from "ckeditor";
import { TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT } from "../../../../constants/fields-constants.js";

export default {
    name: "TextField",
    props: {
        field: Object,
        disabled: Boolean,
        value: Object
    },
    data() {
        return {
            editor: null
        };
    },
    computed: {
        format: {
            get() {
                return this.value.format;
            },
            set(value) {
                if (value === TEXT_FORMAT_HTML) {
                    this.createCKEditor();
                } else {
                    this.destroyCKEditor();
                }
                this.$emit("input", { format: value, content: this.content });
            }
        },
        content: {
            get() {
                return this.value.content;
            },
            // This is only called by the textarea directly, not by CKEditor
            set(value) {
                this.$emit("input", { format: this.format, content: value });
            }
        },
        id() {
            return "tracker_field_" + this.field.field_id;
        },
        is_required_and_empty() {
            return this.field.required && this.content === "";
        },
        text_format() {
            return TEXT_FORMAT_TEXT;
        },
        html_format() {
            return TEXT_FORMAT_HTML;
        },
        ckeditor_config() {
            return {
                toolbar: [
                    ["Bold", "Italic", "Underline"],
                    ["NumberedList", "BulletedList", "-", "Blockquote", "Format"],
                    ["Link", "Unlink", "Anchor", "Image"],
                    ["Source"]
                ],
                height: "100px",
                readOnly: this.disabled
            };
        }
    },
    beforeDestroy() {
        this.destroyCKEditor();
    },
    mounted() {
        if (this.format === TEXT_FORMAT_HTML) {
            this.createCKEditor();
        }
    },
    methods: {
        createCKEditor() {
            this.editor = CKEDITOR.replace(this.$refs.ckeditor_mount_point, this.ckeditor_config);
            this.editor.on("instanceReady", this.onInstanceReady);
        },
        destroyCKEditor() {
            if (this.editor) {
                this.editor.destroy();
            }
        },
        onInstanceReady() {
            this.editor.on("change", this.onChange);

            this.editor.on("mode", () => {
                if (this.editor.mode === "source") {
                    const editable = this.editor.editable();
                    editable.attachListener(editable, "input", () => {
                        this.onChange();
                    });
                }
            });
        },
        onChange() {
            const new_content = this.editor.getData();

            // Editor#change event might be fired without actual data change.
            if (this.content !== new_content) {
                this.$emit("input", { format: this.format, content: new_content });
            }
        }
    }
};
</script>
