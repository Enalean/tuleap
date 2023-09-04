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

import type { FileLineHandle } from "../types-codemirror-overriden";
import {
    doesHandleHaveWidgets,
    isANewInlineCommentWidget,
    isCodeCommentPlaceholderWidget,
    isCommentWidget,
} from "./side-by-side-line-widgets-helper";

import { FileDiffWidgetStub } from "../../../../tests/stubs/FileDiffWidgetStub";
import { FileLineHandleStub } from "../../../../tests/stubs/FileLineHandleStub";

describe("side-by-side-widget-helper", () => {
    describe("doesHandleHaveWidgets", () => {
        it.each([
            [
                true,
                "has widgets",
                FileLineHandleStub.buildLineHandleWithWidgets([
                    FileDiffWidgetStub.buildInlineCommentWidget(),
                ]),
            ],
            [false, "has no widgets", FileLineHandleStub.buildLineHandleWithWidgets([])],
            [false, "is a simple LineHandle", FileLineHandleStub.buildLineHandleWithNoWidgets()],
            [
                false,
                "is a broken LineHandle (yes, they can be)",
                { widgets: null } as unknown as FileLineHandle,
            ],
        ])("should return %s when the handle %s", (has_widgets, handle_description, handle) => {
            expect(doesHandleHaveWidgets(handle)).toBe(has_widgets);
        });
    });

    describe("isCodeCommentPlaceholderWidget", () => {
        it.each([
            [true, "a code placeholder", FileDiffWidgetStub.buildCodeCommentPlaceholder()],
            [false, "a comment placeholder", FileDiffWidgetStub.buildCodePlaceholder()],
            [false, "an inline-comment", FileDiffWidgetStub.buildInlineCommentWidget()],
            [false, "a new-comment-form", FileDiffWidgetStub.buildNewCommentFormWidget()],
        ])(
            "should return %s when the widget is %s",
            (is_comment_placeholder, widget_name, widget) => {
                expect(isCodeCommentPlaceholderWidget(widget)).toBe(is_comment_placeholder);
            },
        );
    });

    describe("isCommentWidget", () => {
        it.each([
            [false, "a code placeholder", FileDiffWidgetStub.buildCodePlaceholder()],
            [false, "a comment placeholder", FileDiffWidgetStub.buildCodeCommentPlaceholder()],
            [true, "an inline-comment", FileDiffWidgetStub.buildInlineCommentWidget()],
            [true, "a new comment form", FileDiffWidgetStub.buildNewCommentFormWidget()],
        ])("should return %s when the widget is %s", (expected_result, widget_name, widget) => {
            expect(isCommentWidget(widget)).toBe(expected_result);
        });
    });

    describe("isAnInlineCommentWidget", () => {
        it.each([
            [false, "a code placeholder", FileDiffWidgetStub.buildCodePlaceholder()],
            [false, "a comment placeholder", FileDiffWidgetStub.buildCodeCommentPlaceholder()],
            [false, "an inline-comment", FileDiffWidgetStub.buildInlineCommentWidget()],
            [true, "a new comment form", FileDiffWidgetStub.buildNewCommentFormWidget()],
        ])("should return %s when the widget is %s", (expected_result, widget_name, widget) => {
            expect(isANewInlineCommentWidget(widget)).toBe(expected_result);
        });
    });
});
