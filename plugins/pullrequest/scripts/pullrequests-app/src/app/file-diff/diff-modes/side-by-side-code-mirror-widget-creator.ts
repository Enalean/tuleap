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

import type { PlaceholderCreationParams } from "./types-codemirror-overriden";
import type { CurrentPullRequestUserPresenter } from "../../comments/PullRequestCurrentUserPresenter";
import type { PullRequestPresenter } from "../../comments/PullRequestPresenter";
import type { IRelativeDateHelper } from "../../helpers/date-helpers";
import type { ControlPullRequestComment } from "../../comments/PullRequestCommentController";
import type { StorePullRequestCommentReplies } from "../../comments/PullRequestCommentRepliesStore";
import type { MapCommentWidgets } from "./file-diff-comment-widgets-map";
import type {
    InlineCommentWidgetCreationParams,
    NewInlineCommentFormWidgetCreationParams,
} from "./types-codemirror-overriden";

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

export interface CreatePlaceholderWidget {
    displayPlaceholderWidget: (widget_params: PlaceholderCreationParams) => void;
}

export interface CreateInlineCommentWidget {
    displayInlineCommentWidget: (widget_params: InlineCommentWidgetCreationParams) => void;
}

export interface CreateNewInlineCommentFormWidget {
    displayNewInlineCommentFormWidget: (
        widget_params: NewInlineCommentFormWidgetCreationParams
    ) => void;
}

export type CreateFileDiffWidget = CreatePlaceholderWidget &
    CreateInlineCommentWidget &
    CreateNewInlineCommentFormWidget;

export const SideBySideCodeMirrorWidgetCreator = (
    doc: Document,
    relative_dates_helper: IRelativeDateHelper,
    controller: ControlPullRequestComment,
    comments_store: StorePullRequestCommentReplies,
    comments_widgets_map: MapCommentWidgets,
    pull_request_presenter: PullRequestPresenter,
    current_user_presenter: CurrentPullRequestUserPresenter
): CreateFileDiffWidget => {
    const displayInlineCommentWidget = (widget_params: InlineCommentWidgetCreationParams): void => {
        const inline_comment_element = doc.createElement(COMMENT_TAG_NAME);
        if (!isPullRequestCommentWidget(inline_comment_element)) {
            return;
        }

        inline_comment_element.setAttribute("class", "inline-comment-element");
        inline_comment_element.comment = widget_params.comment;
        inline_comment_element.relativeDateHelper = relative_dates_helper;
        inline_comment_element.controller = controller;
        inline_comment_element.currentUser = current_user_presenter;
        inline_comment_element.currentPullRequest = pull_request_presenter;

        const widget = widget_params.code_mirror.addLineWidget(
            widget_params.line_number,
            inline_comment_element,
            getWidgetPlacementOptions(widget_params)
        );

        inline_comment_element.post_rendering_callback = (): void => {
            widget_params.post_rendering_callback();
            widget.changed();
        };
        comments_widgets_map.addCommentWidget(inline_comment_element);
    };

    return {
        displayPlaceholderWidget: (widget_params: PlaceholderCreationParams): void => {
            const placeholder = doc.createElement(PLACEHOLDER_TAG_NAME);
            if (!isPlaceholderWidget(placeholder)) {
                return;
            }

            placeholder.height = widget_params.widget_height;
            placeholder.isReplacingAComment = widget_params.is_comment_placeholder;

            const widget = widget_params.code_mirror.addLineWidget(
                widget_params.handle,
                placeholder,
                {
                    coverGutter: true,
                    above: widget_params.display_above_line,
                }
            );

            placeholder.post_rendering_callback = (): void => {
                widget.changed();
            };
        },
        displayInlineCommentWidget,
        displayNewInlineCommentFormWidget: (
            widget_params: NewInlineCommentFormWidgetCreationParams
        ): void => {
            const new_comment_element = doc.createElement(NEW_COMMENT_FORM_TAG_NAME);
            if (!isANewInlineCommentWidget(new_comment_element)) {
                return;
            }

            new_comment_element.comment_saver = NewInlineCommentSaver(widget_params.context);
            const widget = widget_params.code_mirror.addLineWidget(
                widget_params.line_number,
                new_comment_element,
                getWidgetPlacementOptions(widget_params)
            );

            new_comment_element.post_rendering_callback = (): void => {
                widget_params.post_rendering_callback();
                widget.changed();
            };

            new_comment_element.post_submit_callback = (comment_presenter): void => {
                widget.clear();
                comments_store.addRootComment(comment_presenter);

                displayInlineCommentWidget({
                    code_mirror: widget_params.code_mirror,
                    comment: comment_presenter,
                    line_number: widget_params.line_number,
                    post_rendering_callback: widget_params.post_rendering_callback,
                });
            };

            new_comment_element.on_cancel_callback = (): void => {
                widget.clear();
                widget_params.post_rendering_callback();
            };
        },
    };
};
