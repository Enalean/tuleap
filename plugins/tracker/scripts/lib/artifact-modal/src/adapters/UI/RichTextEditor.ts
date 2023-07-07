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
import type { UploadError } from "@tuleap/ckeditor-image-upload";
import {
    buildFileUploadHandler,
    isThereAnImageWithDataURI,
    MaxSizeUploadExceededError,
} from "@tuleap/ckeditor-image-upload";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import {
    isValidTextFormat,
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
} from "@tuleap/plugin-tracker-constants";
import { Option } from "@tuleap/option";
import {
    getNoPasteMessage,
    getRTEHelpMessage,
    getSubmitDisabledImageUploadReason,
    getUploadError,
    getUploadSizeExceeded,
} from "../../gettext-catalog";
import type { FormattedTextControllerType } from "../../domain/common/FormattedTextController";
import type { FileUploadSetup } from "../../domain/fields/file-field/FileUploadSetup";
import { WillDisableSubmit } from "../../domain/submit/WillDisableSubmit";
import { DidUploadImage } from "../../domain/fields/file-field/DidUploadImage";

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
    upload_setup: Option<FileUploadSetup>;
    readonly controller: FormattedTextControllerType;
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
    dispatch(host, "change", { bubbles: true });
    dispatch(host, "content-change", { detail: { content: new_content } });
}

export function setupImageUpload(host: HostElement, ckeditor: CKEDITOR.editor): void {
    if (host.upload_setup.isNothing()) {
        disablePasteOfImages(ckeditor);
        return;
    }
    host.upload_setup.apply((upload_setup) => {
        const onStartCallback = (): void =>
            host.controller.onFileUploadStart(
                WillDisableSubmit(getSubmitDisabledImageUploadReason())
            );
        const onErrorCallback = (error: MaxSizeUploadExceededError | UploadError): void => {
            if (error instanceof MaxSizeUploadExceededError) {
                error.loader.message = sprintf(
                    getUploadSizeExceeded(),
                    prettyKibibytes(error.max_size_upload)
                );
            } else {
                error.loader.message = getUploadError();
            }
            host.controller.onFileUploadError();
        };
        const onSuccessCallback = (id: number, download_href: string): void => {
            host.controller.onFileUploadSuccess(DidUploadImage({ id, download_href }));
        };

        const fileUploadRequestHandler = buildFileUploadHandler({
            ckeditor_instance: ckeditor,
            max_size_upload: upload_setup.max_size_upload,
            onStartCallback,
            onErrorCallback,
            onSuccessCallback,
        });

        ckeditor.on("fileUploadRequest", fileUploadRequestHandler, null, null, 4);
    });
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
    const default_format = host.controller.getDefaultTextFormat();
    const editor_factory = RichTextEditorFactory.forBurningParrotWithExistingFormatSelector(
        document,
        locale,
        default_format
    );

    return editor_factory.createRichTextEditor(host.textarea, {
        format_selectbox_id: "format_" + host.identifier,
        format_selectbox_value: host.format,
        getAdditionalOptions: () =>
            host.controller.getFileUploadSetup().mapOr(
                (upload_setup) => ({
                    height: "100px",
                    readOnly: host.disabled,
                    extraPlugins: "uploadimage",
                    uploadUrl: "/api/v1/" + upload_setup.file_creation_uri,
                }),
                { height: "100px", readOnly: host.disabled }
            ),
        onFormatChange: (new_format) => {
            host.is_help_shown =
                host.controller.getFileUploadSetup().isValue() && new_format === TEXT_FORMAT_HTML;
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
    upload_setup: {
        get: (host, last_value) => last_value ?? Option.nothing(),
        set: (host, new_value) => new_value,
    },
    is_help_shown: false,
    controller: {
        set(host, controller: FormattedTextControllerType) {
            host.upload_setup = controller.getFileUploadSetup();
            return controller;
        },
    },
    content: (host) => html`
        <textarea
            data-textarea
            data-test="textarea"
            id="${host.identifier}"
            required="${host.required}"
            disabled="${host.disabled}"
            class="tlp-textarea"
            rows="${host.rows}"
            maxlength="65535"
            oninput="${onTextareaInput}"
        >
${host.contentValue}</textarea
        >
        ${host.is_help_shown &&
        html`<p data-test="help" class="tlp-text-muted">${getRTEHelpMessage()}</p>`}
    `,
});
