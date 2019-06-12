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
    <textarea
        v-bind:id="id"
        v-model="content"
        v-bind:required="required"
        v-bind:disabled="disabled"
        class="tlp-textarea"
        v-bind:rows="rows"
    ></textarea>
</template>
<script>
import CKEDITOR from "ckeditor";
import { TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT } from "../../../constants/fields-constants.js";

export default {
    name: "RichTextEditor",
    props: {
        id: String,
        format: {
            type: String,
            validator(value) {
                return [TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT].includes(value);
            }
        },
        value: {
            type: String,
            default: ""
        },
        disabled: Boolean,
        required: Boolean,
        rows: {
            type: String,
            default: "5"
        }
    },
    data() {
        return {
            editor: null
        };
    },
    computed: {
        content: {
            get() {
                return this.value;
            },
            // This is only called by the textarea directly, not by CKEditor
            set(value) {
                this.$emit("input", value);
            }
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
    watch: {
        format(new_format) {
            if (new_format === TEXT_FORMAT_HTML) {
                this.createCKEditor();
            } else {
                this.destroyCKEditor();
            }
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
            this.editor = CKEDITOR.replace(this.$el, this.ckeditor_config);
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
                this.$emit("input", new_content);
            }
        }
    }
};
</script>
