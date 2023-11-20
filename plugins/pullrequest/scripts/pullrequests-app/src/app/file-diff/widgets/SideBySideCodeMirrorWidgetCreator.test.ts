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
import type { CreateFileDiffWidget } from "./SideBySideCodeMirrorWidgetCreator";
import type { FileLineHandle } from "../types-codemirror-overriden";
import type {
    InlineCommentWidget,
    FileDiffPlaceholderWidget,
    NewInlineCommentFormWidget,
} from "../types";

import { PullRequestCommentPresenterStub } from "../../../../tests/stubs/PullRequestCommentPresenterStub";
import { SideBySideCodeMirrorWidgetCreator } from "./SideBySideCodeMirrorWidgetCreator";
import { InlineCommentContextStub } from "../../../../tests/stubs/InlineCommentContextStub";

import {
    PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME,
    PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME,
    PullRequestCommentRepliesStore,
} from "@tuleap/plugin-pullrequest-comments";
import { TAG_NAME as PLACEHOLDER_TAG_NAME } from "./placeholders/FileDiffPlaceholder";
import type {
    ControlPullRequestComment,
    StorePullRequestCommentReplies,
} from "@tuleap/plugin-pullrequest-comments";
import { FileDiffCommentWidgetsMap } from "../scroll-to-comment/FileDiffCommentWidgetsMap";
import {
    INLINE_COMMENT_POSITION_LEFT,
    TYPE_INLINE_COMMENT,
    FORMAT_TEXT,
} from "@tuleap/plugin-pullrequest-constants";

type EditorThatCanHaveWidgets = Editor & {
    addLineWidget: jest.SpyInstance;
    getLineHandle: () => FileLineHandle;
};

const project_id = 105;
const is_comment_edition_enabled = true;

describe("side-by-side-code-mirror-widget-creator", () => {
    let doc: Document,
        createElement: jest.SpyInstance,
        code_mirror: EditorThatCanHaveWidgets,
        controller: ControlPullRequestComment,
        comments_store: StorePullRequestCommentReplies;

    const getWidgetCreator = (): CreateFileDiffWidget =>
        SideBySideCodeMirrorWidgetCreator(
            doc,
            controller,
            comments_store,
            FileDiffCommentWidgetsMap(),
            is_comment_edition_enabled,
        );

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        createElement = jest.spyOn(doc, "createElement");

        code_mirror = {
            addLineWidget: jest.fn(),
            getLineHandle: () => ({ widgets: [] }),
        } as unknown as EditorThatCanHaveWidgets;

        controller = {
            displayReplies: (): void => {
                // do nothing
            },
            getRelativeDateHelper: (): void => {
                // do nothing
            },
            getProjectId: () => project_id,
        } as unknown as ControlPullRequestComment;
        comments_store = PullRequestCommentRepliesStore([]);
    });

    describe("displayPlaceholderWidget()", () => {
        it(`should create a tuleap-pullrequest-placeholder that:
            - is added to the target codemirror
            - notifies codemirror that the widget height has changed when the post_submit_callback is run`, () => {
            const widget_creation_params = {
                code_mirror,
                handle: { line_number: 10 } as unknown as FileLineHandle,
                widget_height: 60,
                display_above_line: true,
                is_comment_placeholder: false,
            };

            const placeholder = document.createElement(
                PLACEHOLDER_TAG_NAME,
            ) as FileDiffPlaceholderWidget;
            createElement.mockReturnValue(placeholder);

            const line_widget = {
                changed: jest.fn(),
            };
            code_mirror.addLineWidget.mockReturnValueOnce(line_widget);

            getWidgetCreator().displayPlaceholderWidget(widget_creation_params);

            expect(placeholder.height).toStrictEqual(widget_creation_params.widget_height);
            expect(placeholder.isReplacingAComment).toBe(
                widget_creation_params.is_comment_placeholder,
            );

            expect(code_mirror.addLineWidget).toHaveBeenCalledWith(
                widget_creation_params.handle,
                placeholder,
                {
                    coverGutter: true,
                    above: widget_creation_params.display_above_line,
                },
            );

            placeholder.post_rendering_callback();
            expect(line_widget.changed).toHaveBeenCalledTimes(1);
        });
    });

    describe("displayInlineCommentWidget()", () => {
        it(`should create a tuleap-pull-request-element that:
            - is added to the target codemirror
            - notifies codemirror that the widget height has changed when the post_submit_callback is run`, () => {
            const comment = PullRequestCommentPresenterStub.buildFileDiffCommentPresenter();
            const post_rendering_callback = jest.fn();

            const inline_comment_widget = document.createElement(
                PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME,
            ) as InlineCommentWidget;
            createElement.mockReturnValue(inline_comment_widget);

            const line_widget = {
                changed: jest.fn(),
            };
            code_mirror.addLineWidget.mockReturnValueOnce(line_widget);

            getWidgetCreator().displayInlineCommentWidget({
                code_mirror,
                comment,
                line_number: 12,
                post_rendering_callback,
            });

            expect(inline_comment_widget.comment).toStrictEqual(comment);
            expect(inline_comment_widget.controller).toStrictEqual(controller);
            expect(inline_comment_widget.post_rendering_callback).toBeDefined();

            expect(code_mirror.addLineWidget).toHaveBeenCalledWith(12, inline_comment_widget, {
                coverGutter: true,
            });

            inline_comment_widget.post_rendering_callback();
            expect(line_widget.changed).toHaveBeenCalledTimes(1);
            expect(post_rendering_callback).toHaveBeenCalledTimes(1);
        });
    });

    describe("displayNewInlineCommentFormWidget", () => {
        it(`should create a tuleap-pullrequest-new-comment-form that:
            - is added to the target codemirror
            - notifies codemirror that the widget height has changed when the post_submit_callback is run
            - is removed when the new comment has been submitted
            - is replaced by a tuleap-pull-request-element afterwards`, () => {
            const context = InlineCommentContextStub.widthDefaultContext();
            const post_rendering_callback = jest.fn();

            const new_comment_form = document.createElement(
                PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME,
            ) as NewInlineCommentFormWidget;
            const inline_comment_widget = document.createElement(
                PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME,
            ) as InlineCommentWidget;
            createElement.mockReturnValueOnce(new_comment_form);
            createElement.mockReturnValueOnce(inline_comment_widget);

            const line_widget = {
                node: new_comment_form,
                clear: jest.fn(),
                changed: jest.fn(),
            };
            code_mirror.addLineWidget.mockReturnValueOnce(line_widget);

            getWidgetCreator().displayNewInlineCommentFormWidget({
                code_mirror,
                pull_request_id: 1,
                project_id: 105,
                user_id: 102,
                user_avatar_url: "url/to/user_avatar.png",
                line_number: 15,
                context,
                post_rendering_callback,
            });

            expect(new_comment_form.post_rendering_callback).toBeDefined();
            expect(new_comment_form.controller).toBeDefined();

            expect(code_mirror.addLineWidget).toHaveBeenCalledWith(15, new_comment_form, {
                coverGutter: true,
            });

            new_comment_form.post_rendering_callback();
            expect(line_widget.changed).toHaveBeenCalledTimes(1);
            expect(post_rendering_callback).toHaveBeenCalledTimes(1);

            const new_comment_payload = {
                type: TYPE_INLINE_COMMENT,
                is_outdated: false,
                color: "",
                id: 12,
                position: INLINE_COMMENT_POSITION_LEFT,
                parent_id: 0,
                content: "Please give more precisions",
                raw_content: "Please give more precisions",
                post_processed_content: "Please give more precisions",
                format: FORMAT_TEXT,
                file_path: "README.md",
                user: {
                    id: 102,
                    avatar_url: "url/to/user_avatar.png",
                    user_url: "url/to/user_profile.html",
                    display_name: "Joe l'Asticot",
                },
                post_date: "2023-03-07T16:15:00Z",
                last_edition_date: null,
                unidiff_offset: 15,
            };

            new_comment_form.controller.triggerPostSubmitCallback(new_comment_payload);

            expect(line_widget.clear).toHaveBeenCalledTimes(1);
            expect(comments_store.getAllRootComments()).toHaveLength(1);

            expect(code_mirror.addLineWidget).toHaveBeenCalledWith(15, inline_comment_widget, {
                coverGutter: true,
            });
        });
    });
});
