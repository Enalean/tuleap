/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { UpdateFunction } from "hybrids";
import { html, dispatch } from "hybrids";
import { sanitize } from "dompurify";
import { getCommonMarkPreviewErrorIntroduction } from "../gettext-catalog";
import type { TextFieldFormat } from "../../../../constants/fields-constants";
import { postInterpretCommonMark } from "../api/tuleap-api";
import "../common/FormatSelector";
import "../common/RichTextEditor";

export interface TextAndFormat {
    label: string;
    format: TextFieldFormat;
    contentValue: string;
    required: boolean;
    disabled: boolean;
    projectId: number;
    interpreted_commonmark: string;
    is_in_preview_mode: boolean;
    is_preview_loading: boolean;
    has_error: boolean;
    error_message: string;
}

export type HostElement = TextAndFormat & HTMLElement;

export interface TextAndFormatOptions {
    identifier: string;
    rows: number;
    onContentChange: (host: never, event: CustomEvent) => void;
    onFormatChange: (host: never, event: CustomEvent) => void;
}

export const interpretCommonMark = async (host: TextAndFormat, content: string): Promise<void> => {
    host.has_error = false;
    host.error_message = "";

    if (host.is_in_preview_mode) {
        host.is_in_preview_mode = false;
        return;
    }
    try {
        host.is_preview_loading = true;
        host.interpreted_commonmark = await postInterpretCommonMark(content, host.projectId);
    } catch (error) {
        host.has_error = true;
        if (error instanceof Error) {
            host.error_message = error.message;
        }
    } finally {
        host.is_in_preview_mode = true;
        host.is_preview_loading = false;
    }
};

const togglePreview = (host: TextAndFormat): void => {
    interpretCommonMark(host, host.contentValue);
};

export const onUploadImage = (host: HostElement, event: CustomEvent): void => {
    dispatch(host, "upload-image", { detail: event.detail });
};

const getRichTextEditorClass = (host: TextAndFormat): string[] =>
    host.is_in_preview_mode || host.has_error ? ["tuleap-artifact-modal-hidden"] : [];

const isPreviewShown = (host: TextAndFormat): boolean => host.is_in_preview_mode && !host.has_error;

export const isDisabled = (host: TextAndFormat): boolean =>
    host.disabled || host.is_preview_loading;

export const getTextAndFormatTemplate = (
    host: TextAndFormat,
    options: TextAndFormatOptions
): UpdateFunction<TextAndFormat> => html`
    <tuleap-artifact-modal-format-selector
        identifier="${options.identifier}"
        label="${host.label}"
        disabled="${isDisabled(host)}"
        required="${host.required}"
        value="${host.format}"
        isInPreviewMode="${host.is_in_preview_mode}"
        isPreviewLoading="${host.is_preview_loading}"
        oninterpret-content-event="${togglePreview}"
        data-test="format-selector"
    ></tuleap-artifact-modal-format-selector>
    <tuleap-artifact-modal-rich-text-editor
        class="${getRichTextEditorClass(host)}"
        identifier="${options.identifier}"
        contentValue="${host.contentValue}"
        format="${host.format}"
        disabled="${isDisabled(host)}"
        required="${host.required}"
        rows="${options.rows}"
        oncontent-change="${options.onContentChange}"
        onupload-image="${onUploadImage}"
        onformat-change="${options.onFormatChange}"
        data-test="text-editor"
    ></tuleap-artifact-modal-rich-text-editor>
    ${isPreviewShown(host) &&
    html`
        <div
            innerHTML="${sanitize(host.interpreted_commonmark)}"
            data-test="text-field-commonmark-preview"
        ></div>
    `}
    ${host.has_error &&
    html`
        <div class="tlp-alert-danger" data-test="text-field-error">
            ${getCommonMarkPreviewErrorIntroduction()}${host.error_message}
        </div>
    `}
`;
