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

import type { Editor } from "codemirror";
import type { PlaceholderCreationParams } from "./types-codemirror-overriden";
import type { CurrentPullRequestUserPresenter } from "../../comments/PullRequestCurrentUserPresenter";
import type { PullRequestPresenter } from "../../comments/PullRequestPresenter";
import type { PullRequestInlineCommentPresenter } from "../../comments/PullRequestCommentPresenter";
import type { IRelativeDateHelper } from "../../helpers/date-helpers";
import type { ControlPullRequestComment } from "../../comments/PullRequestCommentController";
import type { InlineCommentContext } from "../../comments/new-comment-form/NewInlineCommentContext";
import type { StorePullRequestCommentReplies } from "../../comments/PullRequestCommentRepliesStore";

import { getWidgetPlacementOptions } from "./file-line-widget-placement-helper";
import {
    isANewInlineCommentWidget,
    isPlaceholderWidget,
    isPullRequestCommentWidget,
} from "./side-by-side-line-widgets-helper";
import { NewInlineCommentSaver } from "../../comments/new-comment-form/NewInlineCommentSaver";

import { TAG_NAME as NEW_COMMENT_FORM_TAG_NAME } from "../../comments/new-comment-form/NewInlineCommentForm";
import { TAG_NAME as COMMENT_TAG_NAME } from "../../comments/PullRequestComment";
import { TAG_NAME as PLACEHOLDER_TAG_NAME } from "../FileDiffPlaceholder";

export interface CreateFileDiffWidget {
    displayPlaceholderWidget: (widget_params: PlaceholderCreationParams) => void;
    displayInlineCommentWidget: (
        code_mirror: Editor,
        comment: PullRequestInlineCommentPresenter,
        line_number: number,
        post_rendering_callback: () => void
    ) => void;
    displayNewInlineCommentFormWidget: (
        code_mirror: Editor,
        widget_line_number: number,
        new_inline_comment_context: InlineCommentContext,
        post_rendering_callback: () => void
    ) => void;
}

export const SideBySideCodeMirrorWidgetCreator = (
    doc: Document,
    relative_dates_helper: IRelativeDateHelper,
    controller: ControlPullRequestComment,
    comments_store: StorePullRequestCommentReplies,
    pull_request_presenter: PullRequestPresenter,
    current_user_presenter: CurrentPullRequestUserPresenter
): CreateFileDiffWidget => {
    const displayInlineCommentWidget = (
        code_mirror: Editor,
        comment: PullRequestInlineCommentPresenter,
        line_number: number,
        post_rendering_callback: () => void
    ): void => {
        const inline_comment_element = doc.createElement(COMMENT_TAG_NAME);
        if (!isPullRequestCommentWidget(inline_comment_element)) {
            return;
        }

        inline_comment_element.setAttribute("class", "inline-comment-element");
        inline_comment_element.comment = comment;
        inline_comment_element.relativeDateHelper = relative_dates_helper;
        inline_comment_element.controller = controller;
        inline_comment_element.currentUser = current_user_presenter;
        inline_comment_element.currentPullRequest = pull_request_presenter;
        inline_comment_element.post_rendering_callback = post_rendering_callback;

        const options = getWidgetPlacementOptions(code_mirror, line_number);

        code_mirror.addLineWidget(line_number, inline_comment_element, options);
    };

    return {
        displayPlaceholderWidget: (widget_params: PlaceholderCreationParams): void => {
            const placeholder = doc.createElement(PLACEHOLDER_TAG_NAME);
            if (!isPlaceholderWidget(placeholder)) {
                return;
            }

            placeholder.height = widget_params.widget_height;
            placeholder.isReplacingAComment = widget_params.is_comment_placeholder;

            widget_params.code_mirror.addLineWidget(widget_params.handle, placeholder, {
                coverGutter: true,
                above: widget_params.display_above_line,
            });
        },
        displayInlineCommentWidget,
        displayNewInlineCommentFormWidget: (
            code_mirror: Editor,
            widget_line_number: number,
            new_inline_comment_context: InlineCommentContext,
            post_rendering_callback: () => void
        ): void => {
            const new_comment_element = doc.createElement(NEW_COMMENT_FORM_TAG_NAME);
            if (!isANewInlineCommentWidget(new_comment_element)) {
                return;
            }

            new_comment_element.comment_saver = NewInlineCommentSaver(new_inline_comment_context);
            new_comment_element.post_rendering_callback = post_rendering_callback;

            const widget = code_mirror.addLineWidget(
                widget_line_number,
                new_comment_element,
                getWidgetPlacementOptions(code_mirror, widget_line_number)
            );

            new_comment_element.post_submit_callback = (comment_presenter): void => {
                widget.clear();
                comments_store.addRootComment(comment_presenter);

                displayInlineCommentWidget(
                    code_mirror,
                    comment_presenter,
                    widget_line_number,
                    post_rendering_callback
                );
            };

            new_comment_element.on_cancel_callback = (): void => {
                widget.clear();
                post_rendering_callback();
            };
        },
    };
};
