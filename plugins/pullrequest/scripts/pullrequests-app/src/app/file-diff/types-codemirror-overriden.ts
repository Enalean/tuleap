/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { LineHandle, LineWidget, Editor } from "codemirror";
import type { FileDiffWidget } from "./types";
import type { PullRequestInlineCommentPresenter } from "@tuleap/plugin-pullrequest-comments";
import type { InlineCommentContext } from "../comments/new-comment-form/NewInlineCommentContext";

/**
 * @types/codemirror's type definition for LineHandle is too minimalist
 * and does not contain a "height" property. We need to override it to
 * be able to compute code placeholders heights.
 */
export interface LineHandleWithAHeight extends LineHandle {
    height: number;
}

/**
 * @types/codemirror's type definition for LineWidget is too minimalist
 * and does not contain a "node" property. We need to override it to be
 * able to retrieve and filter widgets by their types.
 */
export interface LineWidgetWithNode extends LineWidget {
    node: FileDiffWidget;
}

/**
 * @types/codemirror's type definition for LineHandle is too minimalist
 * and does not contain a "widgets" property. We need to override it to
 * be able to retrieve the list of widgets given a LineHandle.
 */
export interface LineHandleWithWidgets extends LineHandleWithAHeight {
    widgets: LineWidgetWithNode[];
}

export type FileLineHandle = LineHandleWithWidgets | LineHandleWithAHeight | LineHandle;

export interface PlaceholderCreationParams {
    code_mirror: Editor;
    handle: FileLineHandle;
    widget_height: number;
    display_above_line: boolean;
    is_comment_placeholder: boolean;
}

export interface CommentWidgetCreationParams {
    code_mirror: Editor;
    line_number: number;
    post_rendering_callback: () => void;
}

export interface InlineCommentWidgetCreationParams extends CommentWidgetCreationParams {
    comment: PullRequestInlineCommentPresenter;
}

export interface NewInlineCommentFormWidgetCreationParams extends CommentWidgetCreationParams {
    context: InlineCommentContext;
}
