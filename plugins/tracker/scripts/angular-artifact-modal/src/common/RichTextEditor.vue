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
        <textarea
            ref="textarea"
            data-test="textarea"
            v-bind:id="id"
            v-model="content"
            v-bind:required="required"
            v-bind:disabled="disabled"
            class="tlp-textarea"
            v-bind:rows="rows"
        ></textarea>
        <p v-if="is_help_shown" key="help" data-test="help" class="tlp-text-muted">
            {{ help_message }}
        </p>
    </div>
</template>
<script>
import { mapGetters } from "vuex";
import CKEDITOR from "ckeditor";
import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import {
    buildFileUploadHandler,
    MaxSizeUploadExceededError,
    UploadError,
} from "../../../../../../src/www/scripts/tuleap/ckeditor/file-upload-handler-factory.js";
import { isThereAnImageWithDataURI } from "../../../../../../src/www/scripts/tuleap/ckeditor/image-urls-finder.js";
import { TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT } from "../../../constants/fields-constants.js";
import {
    setIsNotUploadingInCKEditor,
    setIsUploadingInCKEditor,
} from "../tuleap-artifact-modal-fields/file-field/is-uploading-in-ckeditor-state.js";

export default {
    name: "RichTextEditor",
    props: {
        id: String,
        format: {
            type: String,
            validator(value) {
                return [TEXT_FORMAT_HTML, TEXT_FORMAT_TEXT].includes(value);
            },
        },
        value: {
            type: String,
            default: "",
        },
        disabled: Boolean,
        required: Boolean,
        rows: {
            type: String,
            default: "5",
        },
    },
    data() {
        return {
            editor: null,
        };
    },
    computed: {
        ...mapGetters(["first_file_field"]),

        is_upload_possible() {
            return this.first_file_field !== null;
        },

        is_html_format() {
            return this.format === TEXT_FORMAT_HTML;
        },

        is_help_shown() {
            return this.is_html_format && this.is_upload_possible;
        },

        help_message() {
            // Translate attribute does not work with ng-vue out of the box.
            return this.$gettext("You can drag 'n drop or paste image directly in the editor.");
        },

        content: {
            get() {
                return this.value;
            },
            // This is only called by the textarea directly, not by CKEditor
            set(value) {
                this.$emit("input", value);
            },
        },

        ckeditor_config() {
            let additional_options = {};
            if (this.is_upload_possible) {
                additional_options = {
                    extraPlugins: "uploadimage",
                    uploadUrl: "/api/v1/" + this.first_file_field.file_creation_uri,
                };
            }

            return {
                toolbar: [
                    ["Bold", "Italic", "Underline"],
                    ["NumberedList", "BulletedList", "-", "Blockquote", "Format"],
                    ["Link", "Unlink", "Anchor", "Image"],
                    ["Source"],
                ],
                height: "100px",
                readOnly: this.disabled,
                ...additional_options,
            };
        },
    },
    watch: {
        format(new_format) {
            if (new_format === TEXT_FORMAT_HTML) {
                this.createCKEditor();
            } else {
                this.destroyCKEditor();
            }
        },
    },
    beforeDestroy() {
        this.destroyCKEditor();
    },
    mounted() {
        if (this.is_html_format) {
            this.createCKEditor();
        }
    },
    methods: {
        createCKEditor() {
            this.editor = CKEDITOR.replace(this.$refs.textarea, this.ckeditor_config);
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

            this.setupImageUpload();
        },

        onChange() {
            const new_content = this.editor.getData();

            // Editor#change event might be fired without actual data change.
            if (this.content !== new_content) {
                this.$emit("input", new_content);
            }
        },

        setupImageUpload() {
            if (!this.is_upload_possible) {
                this.disablePasteOfImages();
                return;
            }

            const onStartCallback = setIsUploadingInCKEditor;
            const onErrorCallback = (error) => {
                if (error instanceof MaxSizeUploadExceededError) {
                    error.loader.message = sprintf(
                        this.$gettext("You are not allowed to upload files bigger than %s."),
                        prettyKibibytes(error.max_size_upload)
                    );
                } else if (error instanceof UploadError) {
                    error.loader.message = this.$gettext("Unable to upload the file");
                }
                setIsNotUploadingInCKEditor();
            };
            const onSuccessCallback = (id, download_href) => {
                this.$emit("upload-image", this.first_file_field.field_id, { id, download_href });
                setIsNotUploadingInCKEditor();
            };

            const fileUploadRequestHandler = buildFileUploadHandler({
                ckeditor_instance: this.editor,
                max_size_upload: this.first_file_field.max_size_upload,
                onStartCallback,
                onErrorCallback,
                onSuccessCallback,
            });

            this.editor.on("fileUploadRequest", fileUploadRequestHandler, null, null, 4);
        },

        disablePasteOfImages() {
            this.editor.on("paste", (event) => {
                if (isThereAnImageWithDataURI(event.data.dataValue)) {
                    event.data.dataValue = "";
                    event.cancel();
                    this.editor.showNotification(
                        this.$gettext("You are not allowed to paste images here")
                    );
                }
            });
        },
    },
};
</script>
