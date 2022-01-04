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

import { define, html, dispatch } from "hybrids";
import { sanitize } from "dompurify";
import { getCommentLabel, getCommonMarkPreviewErrorIntroduction } from "../gettext-catalog";
import type { CommonMarkInterpreter } from "../common/interpret-commonmark";
import { interpretCommonMark } from "../common/interpret-commonmark";
import { getValidFormat } from "../common/RichTextEditor";
import "../common/FormatSelector";

export interface FollowupEditor extends CommonMarkInterpreter {
    contentValue: string;
    content: () => HTMLElement;
}
export type HostElement = FollowupEditor & HTMLElement;

const onFormatChange = (host: HostElement, event: CustomEvent): void => {
    const { format, content } = event.detail;
    host.format = format;
    host.contentValue = content;
    dispatch(host, "value-changed", { detail: { format, body: content } });
};

const onContentChange = (host: HostElement, event: CustomEvent): void => {
    const { content } = event.detail;
    host.contentValue = content;
    dispatch(host, "value-changed", { detail: { format: host.format, body: content } });
};

const togglePreview = (host: FollowupEditor): void => {
    interpretCommonMark(host, host.contentValue);
};

const onUploadImage = (host: HostElement, event: CustomEvent): void => {
    dispatch(host, "upload-image", { detail: event.detail });
};

const getRichTextEditorClass = (host: FollowupEditor): string[] =>
    host.is_in_preview_mode || host.has_error ? ["tuleap-artifact-modal-hidden"] : [];

const isPreviewShown = (host: FollowupEditor): boolean =>
    host.is_in_preview_mode && !host.has_error;

const HTML_ID = "followup_comment";

export const FollowupEditor = define<FollowupEditor>({
    tag: "tuleap-artifact-modal-followup-editor",
    contentValue: "",
    format: { set: getValidFormat },
    projectId: 0,
    interpreted_commonmark: "",
    is_in_preview_mode: false,
    is_preview_loading: false,
    has_error: false,
    error_message: "",
    content: (host) => html`
        <tuleap-artifact-modal-format-selector
            identifier="${HTML_ID}"
            label="${getCommentLabel()}"
            value="${host.format}"
            isInPreviewMode="${host.is_in_preview_mode}"
            isPreviewLoading="${host.is_preview_loading}"
            oninterpret-content-event="${togglePreview}"
            data-test="format-selector"
        ></tuleap-artifact-modal-format-selector>
        <tuleap-artifact-modal-rich-text-editor
            class="${getRichTextEditorClass(host)}"
            identifier="${HTML_ID}"
            contentValue="${host.contentValue}"
            format="${host.format}"
            disabled="${host.is_preview_loading}"
            required="${false}"
            rows="3"
            oncontent-change="${onContentChange}"
            onupload-image="${onUploadImage}"
            onformat-change="${onFormatChange}"
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
    `,
});
