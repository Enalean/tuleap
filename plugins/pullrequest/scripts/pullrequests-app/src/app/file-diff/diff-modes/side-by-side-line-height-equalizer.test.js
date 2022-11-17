/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { equalizeSides } from "./side-by-side-line-height-equalizer.js";
import { SideBySidePlaceholderPositioner } from "./side-by-side-placeholder-positioner";
import { FileDiffWidgetStub } from "../../../../tests/stubs/FileDiffWidgetStub";
import { FileLineHandleStub } from "../../../../tests/stubs/FileLineHandleStub";
import { FileLinesStateStub } from "../../../../tests/stubs/FileLinesStateStub";
import { FileLineStub } from "../../../../tests/stubs/FileLineStub";
import { GroupOfLinesStub } from "../../../../tests/stubs/GroupOfLinesStub";

const left_codemirror = "left-codemirror";
const right_codemirror = "right-codemirror";

function getPlaceholderPositioner(handles) {
    const line = FileLineStub.buildUnMovedFileLine(1, 1);

    return SideBySidePlaceholderPositioner(
        FileLinesStateStub.build(
            [line],
            [GroupOfLinesStub.buildGroupOfUnMovedLines([line])],
            new Map([[line, handles]])
        )
    );
}

describe("line-height-equalizer", () => {
    describe("equalizeSides", () => {
        it("Given a line with a new comment, when the opposite line has no comment or placeholder, then it should return some widget creation parameters for the opposite line with height equal to the new_comment widget height.", () => {
            const handles = {
                left_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                    FileDiffWidgetStub.buildInlineCommentWidget(),
                ]),
                right_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
            };

            const placeholder_to_create = equalizeSides(
                left_codemirror,
                right_codemirror,
                handles,
                getPlaceholderPositioner(handles)
            );

            expect(placeholder_to_create).toStrictEqual({
                code_mirror: right_codemirror,
                handle: handles.right_handle,
                widget_height: 20,
                display_above_line: false,
                is_comment_placeholder: true,
            });
        });

        it("Given a line with 1 comment, 1 new comment, when the opposite has a placeholder, then it should adjust the opposite placeholder height.", () => {
            const placeholder = FileDiffWidgetStub.buildCodeCommentPlaceholder(20);
            const handles = {
                left_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                    FileDiffWidgetStub.buildInlineCommentWidget(25),
                    FileDiffWidgetStub.buildNewInlineCommentWidget(20),
                ]),
                right_handle: FileLineHandleStub.buildLineHandleWithWidgets([placeholder]),
            };

            const placeholder_to_create = equalizeSides(
                left_codemirror,
                right_codemirror,
                handles,
                getPlaceholderPositioner(handles)
            );

            expect(placeholder_to_create).toBeUndefined();
            expect(placeholder.height).toBe(45);
        });

        it("Given a line with 2 comments, when the opposite has a placeholder and a new comment is added, then it should reduce the opposite placeholder height.", () => {
            const placeholder = FileDiffWidgetStub.buildCodeCommentPlaceholder(45);
            const handles = {
                left_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                    FileDiffWidgetStub.buildInlineCommentWidget(25),
                    FileDiffWidgetStub.buildInlineCommentWidget(20),
                ]),
                right_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                    FileDiffWidgetStub.buildNewInlineCommentWidget(20),
                    placeholder,
                ]),
            };

            const placeholder_to_create = equalizeSides(left_codemirror, right_codemirror, handles);

            expect(placeholder_to_create).toBeUndefined();
            expect(placeholder.height).toBe(25);
        });

        it("When the two sides have the same number of comments, then the placeholders should be minimized (height 0px).", () => {
            const placeholder = FileDiffWidgetStub.buildCodeCommentPlaceholder(20);
            const handles = {
                left_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                    FileDiffWidgetStub.buildInlineCommentWidget(20),
                ]),
                right_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                    placeholder,
                    FileDiffWidgetStub.buildNewInlineCommentWidget(20),
                ]),
            };

            const placeholder_to_create = equalizeSides(
                left_codemirror,
                right_codemirror,
                handles,
                getPlaceholderPositioner(handles)
            );

            expect(placeholder_to_create).toBeUndefined();
            expect(placeholder.height).toBe(0);
        });
    });

    it("Given a line with a code placeholder (added/deleted line), when a new inline comment is added, a comment placeholder will be added and the code placeholder will remain untouched.", () => {
        const handles = {
            left_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                FileDiffWidgetStub.buildCodePlaceholder(20),
            ]),
            right_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                FileDiffWidgetStub.buildNewInlineCommentWidget(20),
            ]),
        };

        const placeholder_to_create = equalizeSides(
            left_codemirror,
            right_codemirror,
            handles,
            getPlaceholderPositioner(handles)
        );

        expect(placeholder_to_create).toStrictEqual({
            code_mirror: "left-codemirror",
            handle: handles.left_handle,
            widget_height: 20,
            display_above_line: false,
            is_comment_placeholder: true,
        });
    });
});
