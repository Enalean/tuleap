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

import type {
    PlaceholderCreationParams,
    InlineCommentWidgetCreationParams,
    NewInlineCommentFormWidgetCreationParams,
} from "../types-codemirror-overriden";
import type {
    ControlPullRequestComment,
    StorePullRequestCommentReplies,
} from "@tuleap/plugin-pullrequest-comments";
import type { MapCommentWidgets } from "../scroll-to-comment/FileDiffCommentWidgetsMap";
import { getWidgetPlacementOptions } from "./file-line-widget-placement-helper";
import {
    isANewInlineCommentWidget,
    isPlaceholderWidget,
    isPullRequestCommentWidget,
} from "./side-by-side-line-widgets-helper";

import {
    PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME,
    PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME,
    NewCommentSaver,
    NewCommentFormController,
} from "@tuleap/plugin-pullrequest-comments";
import { TAG_NAME as PLACEHOLDER_TAG_NAME } from "./placeholders/FileDiffPlaceholder";
import { PullRequestCommentPresenterBuilder } from "../../comments/PullRequestCommentPresenterBuilder";
import { TYPE_INLINE_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import type { PullRequestComment } from "@tuleap/plugin-pullrequest-rest-api-types";

export interface CreatePlaceholderWidget {
    displayPlaceholderWidget: (widget_params: PlaceholderCreationParams) => void;
}

export interface CreateInlineCommentWidget {
    displayInlineCommentWidget: (widget_params: InlineCommentWidgetCreationParams) => void;
}

export interface CreateNewInlineCommentFormWidget {
    displayNewInlineCommentFormWidget: (
        widget_params: NewInlineCommentFormWidgetCreationParams,
    ) => void;
}

export type CreateFileDiffWidget = CreatePlaceholderWidget &
    CreateInlineCommentWidget &
    CreateNewInlineCommentFormWidget;

export const SideBySideCodeMirrorWidgetCreator = (
    doc: Document,
    controller: ControlPullRequestComment,
    comments_store: StorePullRequestCommentReplies,
    comments_widgets_map: MapCommentWidgets,
    is_comment_edition_enabled: boolean,
): CreateFileDiffWidget => {
    const displayInlineCommentWidget = (widget_params: InlineCommentWidgetCreationParams): void => {
        const inline_comment_element = doc.createElement(PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME);
        if (!isPullRequestCommentWidget(inline_comment_element)) {
            return;
        }

        inline_comment_element.setAttribute("class", "inline-comment-element");
        inline_comment_element.comment = widget_params.comment;
        inline_comment_element.controller = controller;
        inline_comment_element.is_comment_edition_enabled = is_comment_edition_enabled;

        const widget = widget_params.code_mirror.addLineWidget(
            widget_params.line_number,
            inline_comment_element,
            getWidgetPlacementOptions(widget_params),
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
                },
            );

            placeholder.post_rendering_callback = (): void => {
                widget.changed();
            };
        },
        displayInlineCommentWidget,
        displayNewInlineCommentFormWidget: (
            widget_params: NewInlineCommentFormWidgetCreationParams,
        ): void => {
            const new_comment_element = doc.createElement(
                PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME,
            );
            if (!isANewInlineCommentWidget(new_comment_element)) {
                return;
            }

            new_comment_element.setAttribute("class", "inline-comment-element");

            const widget = widget_params.code_mirror.addLineWidget(
                widget_params.line_number,
                new_comment_element,
                getWidgetPlacementOptions(widget_params),
            );

            const post_submit_callback = (comment_payload: PullRequestComment): void => {
                widget.clear();

                if (comment_payload.type !== TYPE_INLINE_COMMENT) {
                    return;
                }

                const comment_presenter =
                    PullRequestCommentPresenterBuilder.fromFileDiffComment(comment_payload);
                comments_store.addRootComment(comment_presenter);

                displayInlineCommentWidget({
                    code_mirror: widget_params.code_mirror,
                    comment: comment_presenter,
                    line_number: widget_params.line_number,
                    post_rendering_callback: widget_params.post_rendering_callback,
                });
            };

            const on_cancel_callback = (): void => {
                widget.clear();
                widget_params.post_rendering_callback();
            };

            new_comment_element.post_rendering_callback = (): void => {
                widget_params.post_rendering_callback();
                widget.changed();
            };

            new_comment_element.controller = NewCommentFormController(
                NewCommentSaver({
                    type: TYPE_INLINE_COMMENT,
                    pull_request_id: widget_params.pull_request_id,
                    user_id: widget_params.user_id,
                    comment_context: widget_params.context,
                }),
                { avatar_url: widget_params.user_avatar_url },
                {
                    is_cancel_allowed: true,
                    is_autofocus_enabled: true,
                    project_id: widget_params.project_id,
                },
                post_submit_callback,
                (fault) => {
                    // eslint-disable-next-line no-console
                    console.error(String(fault));
                },
                on_cancel_callback,
            );
        },
    };
};
