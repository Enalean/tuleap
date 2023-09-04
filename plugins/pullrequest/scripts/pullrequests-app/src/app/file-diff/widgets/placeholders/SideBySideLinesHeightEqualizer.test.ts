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

import type { Editor } from "codemirror";
import type { SynchronizedLineHandles } from "../../file-lines/SideBySideLineMapper";
import type { EqualizeLinesHeights } from "./SideBySideLinesHeightEqualizer";
import { FileDiffWidgetStub } from "../../../../../tests/stubs/FileDiffWidgetStub";
import { FileLineHandleStub } from "../../../../../tests/stubs/FileLineHandleStub";
import { FileLinesStateStub } from "../../../../../tests/stubs/FileLinesStateStub";
import { FileLineStub } from "../../../../../tests/stubs/FileLineStub";
import { GroupOfLinesStub } from "../../../../../tests/stubs/GroupOfLinesStub";
import { SideBySidePlaceholderPositioner } from "./SideBySidePlaceholderPositioner";
import { SideBySideLinesHeightEqualizer } from "./SideBySideLinesHeightEqualizer";

describe("line-height-equalizer", () => {
    let left_codemirror: Editor, right_codemirror: Editor;

    function getLinesHeightsEqualizer(handles: SynchronizedLineHandles): EqualizeLinesHeights {
        const line = FileLineStub.buildUnMovedFileLine(1, 1, 1);

        return SideBySideLinesHeightEqualizer(
            left_codemirror,
            right_codemirror,
            SideBySidePlaceholderPositioner(
                FileLinesStateStub(
                    [line],
                    [GroupOfLinesStub.buildGroupOfUnMovedLines([line])],
                    new Map([[line, handles]]),
                ).getState(),
            ),
        );
    }

    beforeEach(() => {
        left_codemirror = { name: "left-codemirror" } as unknown as Editor;
        right_codemirror = { name: "right-codemirror" } as unknown as Editor;
    });

    describe("equalizeSides", () => {
        it("Given a line with a new comment, when the opposite line has no comment or placeholder, then it should return some widget creation parameters for the opposite line with height equal to the new_comment widget height.", () => {
            const handles = {
                left_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                    FileDiffWidgetStub.buildInlineCommentWidget(),
                ]),
                right_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
            };

            const lines_equalizer = getLinesHeightsEqualizer(handles);
            expect(lines_equalizer.equalizeSides(handles)).toStrictEqual({
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
                    FileDiffWidgetStub.buildNewCommentFormWidget(20),
                ]),
                right_handle: FileLineHandleStub.buildLineHandleWithWidgets([placeholder]),
            };

            const lines_equalizer = getLinesHeightsEqualizer(handles);
            const placeholder_to_create = lines_equalizer.equalizeSides(handles);

            expect(placeholder_to_create).toBeNull();
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
                    FileDiffWidgetStub.buildNewCommentFormWidget(20),
                    placeholder,
                ]),
            };

            const lines_equalizer = getLinesHeightsEqualizer(handles);
            const placeholder_to_create = lines_equalizer.equalizeSides(handles);

            expect(placeholder_to_create).toBeNull();
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
                    FileDiffWidgetStub.buildNewCommentFormWidget(20),
                ]),
            };

            const lines_equalizer = getLinesHeightsEqualizer(handles);
            const placeholder_to_create = lines_equalizer.equalizeSides(handles);

            expect(placeholder_to_create).toBeNull();
            expect(placeholder.height).toBe(0);
        });
    });

    it("Given a line with a code placeholder (added/deleted line), when a new inline comment is added, a comment placeholder will be added and the code placeholder will remain untouched.", () => {
        const handles = {
            left_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                FileDiffWidgetStub.buildCodePlaceholder(20),
            ]),
            right_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                FileDiffWidgetStub.buildNewCommentFormWidget(20),
            ]),
        };

        const lines_equalizer = getLinesHeightsEqualizer(handles);
        const placeholder_to_create = lines_equalizer.equalizeSides(handles);

        expect(placeholder_to_create).toStrictEqual({
            code_mirror: left_codemirror,
            handle: handles.left_handle,
            widget_height: 20,
            display_above_line: false,
            is_comment_placeholder: true,
        });
    });
});
