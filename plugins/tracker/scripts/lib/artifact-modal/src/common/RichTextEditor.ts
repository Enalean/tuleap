/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { define, dispatch, html } from "hybrids";
import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import type { TextEditorInterface } from "@tuleap/plugin-tracker-rich-text-editor";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import {
    isThereAnImageWithDataURI,
    buildFileUploadHandler,
    MaxSizeUploadExceededError,
    UploadError,
} from "@tuleap/ckeditor-image-upload";
import type { TextFieldFormat } from "../../../../constants/fields-constants";
import {
    isValidTextFormat,
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
} from "../../../../constants/fields-constants";
import {
    setIsNotUploadingInCKEditor,
    setIsUploadingInCKEditor,
} from "../fields/file-field/is-uploading-in-ckeditor-state";
import type { FileField } from "../types";
import {
    getNoPasteMessage,
    getRTEHelpMessage,
    getUploadError,
    getUploadSizeExceeded,
} from "../gettext-catalog";
import { getTextFieldDefaultFormat } from "../model/UserPreferencesStore";
import { getFirstFileField } from "../model/FirstFileFieldStore";

export interface RichTextEditor {
    identifier: string;
    format: TextFieldFormat;
    contentValue: string;
    disabled: boolean;
    required: boolean;
    rows: number;
    textarea: HTMLTextAreaElement | null;
    editor: TextEditorInterface | undefined;
    is_help_shown: boolean;
    first_file_field: FileField | null;
    content: () => HTMLElement;
}
export type HostElement = RichTextEditor & HTMLElement;

export const getValidFormat = (
    host: unknown,
    value: string,
    lastValue: TextFieldFormat | undefined
): TextFieldFormat => {
    if (isValidTextFormat(value)) {
        return value;
    }
    return lastValue ?? TEXT_FORMAT_COMMONMARK;
};

export const onTextareaInput = (host: HostElement, event: Event): void => {
    if (!(event.target instanceof HTMLTextAreaElement)) {
        return;
    }
    const text_content = event.target.value;
    host.contentValue = text_content;
    dispatch(host, "content-change", { detail: { content: text_content } });
};

export const onInstanceReady = (host: HostElement, ckeditor: CKEDITOR.editor): void => {
    ckeditor.on("change", () => onChange(host, ckeditor));

    ckeditor.on("mode", () => {
        if (ckeditor.mode === "source") {
            const editable = ckeditor.editable();
            editable.attachListener(editable, "input", () => {
                onChange(host, ckeditor);
            });
        }
    });

    setupImageUpload(host, ckeditor);
};

function onChange(host: HostElement, ckeditor: CKEDITOR.editor): void {
    const new_content = ckeditor.getData();
    // Editor#change event might be fired without actual data change.
    if (host.contentValue === new_content) {
        return;
    }
    host.contentValue = new_content;
    dispatch(host, "content-change", { detail: { content: new_content } });
}

const isUploadPossible = (field: FileField | null): field is FileField => field !== null;

export function setupImageUpload(host: HostElement, ckeditor: CKEDITOR.editor): void {
    if (!isUploadPossible(host.first_file_field)) {
        disablePasteOfImages(ckeditor);
        return;
    }

    const onStartCallback = setIsUploadingInCKEditor;
    const onErrorCallback = (error: MaxSizeUploadExceededError | UploadError): void => {
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
    const field_id = host.first_file_field.field_id;
    const onSuccessCallback = (id: number, download_href: string): void => {
        dispatch(host, "upload-image", {
            detail: {
                field_id,
                image: { id, download_href },
            },
        });
        setIsNotUploadingInCKEditor();
    };

    const fileUploadRequestHandler = buildFileUploadHandler({
        ckeditor_instance: ckeditor,
        max_size_upload: host.first_file_field.max_size_upload,
        onStartCallback,
        onErrorCallback,
        onSuccessCallback,
    });

    ckeditor.on("fileUploadRequest", fileUploadRequestHandler, null, null, 4);
}

function disablePasteOfImages(ckeditor: CKEDITOR.editor): void {
    ckeditor.on("paste", (event) => {
        if (isThereAnImageWithDataURI(event.data.dataValue)) {
            event.data.dataValue = "";
            event.cancel();
            ckeditor.showNotification(getNoPasteMessage(), "info", 0);
        }
    });
}

export const createEditor = (host: HostElement): TextEditorInterface | undefined => {
    if (!host.textarea || host.identifier === "") {
        return undefined;
    }
    const locale = document.body.dataset.userLocale ?? "en_US";
    const default_format = getTextFieldDefaultFormat();
    const editor_factory = RichTextEditorFactory.forBurningParrotWithExistingFormatSelector(
        document,
        locale,
        default_format
    );

    return editor_factory.createRichTextEditor(host.textarea, {
        format_selectbox_id: "format_" + host.identifier,
        format_selectbox_value: host.format,
        getAdditionalOptions: () => {
            if (!isUploadPossible(host.first_file_field)) {
                return { height: "100px", readOnly: host.disabled };
            }
            return {
                height: "100px",
                readOnly: host.disabled,
                extraPlugins: "uploadimage",
                uploadUrl: "/api/v1/" + host.first_file_field.file_creation_uri,
            };
        },
        onFormatChange: (new_format) => {
            host.is_help_shown =
                isUploadPossible(host.first_file_field) && new_format === TEXT_FORMAT_HTML;
            if (!host.textarea) {
                return;
            }
            dispatch(host, "format-change", {
                detail: {
                    format: new_format,
                    content: host.textarea.value,
                },
            });
        },
        onEditorInit: (ckeditor): void => {
            onInstanceReady(host, ckeditor);
        },
    });
};

// Destroy the rich text editor on disconnect
export const connect = (host: RichTextEditor) => (): void => host.editor?.destroy();

export const RichTextEditor = define<RichTextEditor>({
    tag: "tuleap-artifact-modal-rich-text-editor",
    identifier: {
        value: "",
        observe: (host) => {
            // identifier can be empty at connect() time. If we let it be empty,
            // all format selectors will change all editors in the page.
            // We want a single format selector to affect a single editor.
            // identifier is used by @tuleap/plugin-tracker-rich-text-editor
            // to find the format selector matching this editor.
            if (host.editor) {
                return;
            }
            host.editor = createEditor(host);
        },
    },
    editor: { value: undefined, connect },
    format: { set: getValidFormat },
    contentValue: "",
    disabled: false,
    required: false,
    rows: 5,
    textarea: (host) => {
        const target = host.content();
        const textarea = target.querySelector("[data-textarea]");
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return null;
        }
        return textarea;
    },
    first_file_field: {
        get: getFirstFileField,
    },
    is_help_shown: false,
    content: (host) => html`
        <textarea
            data-textarea
            data-test="textarea"
            id="${host.identifier}"
            required="${host.required}"
            disabled="${host.disabled}"
            class="tlp-textarea"
            rows="${host.rows}"
            oninput="${onTextareaInput}"
        >
${host.contentValue}</textarea
        >
        ${host.is_help_shown &&
        html`
            <p data-test="help" class="tlp-text-muted">${getRTEHelpMessage()}</p>
        `}
    `,
});
