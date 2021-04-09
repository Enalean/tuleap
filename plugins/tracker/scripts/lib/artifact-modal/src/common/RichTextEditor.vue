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
import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import {
    isThereAnImageWithDataURI,
    buildFileUploadHandler,
    MaxSizeUploadExceededError,
    UploadError,
} from "@tuleap/ckeditor-image-upload";
import { isValidTextFormat, TEXT_FORMAT_HTML } from "../../../../constants/fields-constants.js";
import {
    setIsNotUploadingInCKEditor,
    setIsUploadingInCKEditor,
} from "../fields/file-field/is-uploading-in-ckeditor-state.js";
import {
    getNoPasteMessage,
    getRTEHelpMessage,
    getUploadError,
    getUploadSizeExceeded,
} from "../gettext-catalog";
import { getTextFieldDefaultFormat } from "../model/UserPreferencesStore";

export default {
    name: "RichTextEditor",
    props: {
        id: String,
        format: {
            type: String,
            validator: isValidTextFormat,
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
            is_help_shown: false,
            editor: null,
        };
    },
    computed: {
        ...mapGetters(["first_file_field"]),

        is_upload_possible() {
            return this.first_file_field !== null;
        },

        help_message() {
            return getRTEHelpMessage();
        },

        content: {
            get() {
                return this.value;
            },
            // This is only called by the textarea directly, not by CKEditor.
            set(value) {
                this.$emit("input", value);
            },
        },
    },
    beforeDestroy() {
        if (this.editor) {
            this.editor.destroy();
        }
    },
    mounted() {
        const body_locale = document.body.dataset.userLocale;
        const locale = body_locale ? body_locale : "en_US";
        const default_format = getTextFieldDefaultFormat();
        const editor_factory = RichTextEditorFactory.forBurningParrotWithExistingFormatSelector(
            document,
            locale,
            default_format
        );

        let additional_options = {
            height: "100px",
            readOnly: this.disabled,
        };
        if (this.is_upload_possible) {
            additional_options = {
                ...additional_options,
                extraPlugins: "uploadimage",
                uploadUrl: "/api/v1/" + this.first_file_field.file_creation_uri,
            };
        }

        const options = {
            format_selectbox_id: "format_" + this.id,
            format_selectbox_value: this.format,
            getAdditionalOptions: () => additional_options,
            onFormatChange: (new_format) => {
                this.is_help_shown = this.is_upload_possible && new_format === TEXT_FORMAT_HTML;
                this.$emit("format-change", new_format, this.$refs.textarea.value);
            },
            onEditorInit: (ckeditor) => {
                this.onInstanceReady(ckeditor);
            },
        };
        this.editor = editor_factory.createRichTextEditor(this.$refs.textarea, options);
    },
    methods: {
        onInstanceReady(ckeditor) {
            ckeditor.on("change", () => this.onChange(ckeditor));

            ckeditor.on("mode", () => {
                if (ckeditor.mode === "source") {
                    const editable = ckeditor.editable();
                    editable.attachListener(editable, "input", () => {
                        this.onChange(ckeditor);
                    });
                }
            });

            this.setupImageUpload(ckeditor);
        },

        onChange(ckeditor) {
            const new_content = ckeditor.getData();

            // Editor#change event might be fired without actual data change.
            if (this.content !== new_content) {
                this.$emit("input", new_content);
            }
        },

        setupImageUpload(ckeditor) {
            if (!this.is_upload_possible) {
                this.disablePasteOfImages(ckeditor);
                return;
            }

            const onStartCallback = setIsUploadingInCKEditor;
            const onErrorCallback = (error) => {
                if (error instanceof MaxSizeUploadExceededError) {
                    error.loader.message = sprintf(
                        getUploadSizeExceeded(),
                        prettyKibibytes(error.max_size_upload)
                    );
                } else if (error instanceof UploadError) {
                    error.loader.message = getUploadError();
                }
                setIsNotUploadingInCKEditor();
            };
            const onSuccessCallback = (id, download_href) => {
                this.$emit("upload-image", this.first_file_field.field_id, { id, download_href });
                setIsNotUploadingInCKEditor();
            };

            const fileUploadRequestHandler = buildFileUploadHandler({
                ckeditor_instance: ckeditor,
                max_size_upload: this.first_file_field.max_size_upload,
                onStartCallback,
                onErrorCallback,
                onSuccessCallback,
            });

            ckeditor.on("fileUploadRequest", fileUploadRequestHandler, null, null, 4);
        },

        disablePasteOfImages(ckeditor) {
            ckeditor.on("paste", (event) => {
                if (isThereAnImageWithDataURI(event.data.dataValue)) {
                    event.data.dataValue = "";
                    event.cancel();
                    ckeditor.showNotification(getNoPasteMessage());
                }
            });
        },
    },
};
</script>
