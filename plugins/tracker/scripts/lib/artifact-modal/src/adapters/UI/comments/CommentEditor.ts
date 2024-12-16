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

import { define, dispatch } from "hybrids";
import type { UpdateFunction } from "hybrids";
import { getCommentLabel } from "../../../gettext-catalog";
import type { TextAndFormat } from "../text-and-format";
import { getTextAndFormatTemplate } from "../text-and-format";
import { getValidFormat } from "../RichTextEditor";
import "../FormatSelector";

export type CommentEditor = TextAndFormat;
export type HostElement = CommentEditor & HTMLElement;

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

export const renderCommentEditor = (host: CommentEditor): UpdateFunction<CommentEditor> =>
    getTextAndFormatTemplate(host, {
        identifier: "followup_comment",
        rows: 3,
        allows_mentions: true,
        onContentChange,
        onFormatChange,
    });

export const CommentEditor = define<CommentEditor>({
    tag: "tuleap-artifact-modal-comment-editor",
    label: () => getCommentLabel(),
    format: getValidFormat,
    contentValue: "",
    required: false,
    disabled: false,
    interpreted_commonmark: "",
    is_in_preview_mode: false,
    is_preview_loading: false,
    has_error: false,
    error_message: "",
    controller: (host, controller) => controller,
    render: renderCommentEditor,
});
