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
import type { FileLine, GroupOfLines, UnMovedFileLine } from "./types";
import type { BuildCommentPlaceholder } from "./side-by-side-comment-placeholder-builder";
import type { SynchronizedLineHandles } from "./side-by-side-line-mapper";

import { FileLineStub } from "../../../../tests/stubs/FileLineStub";
import { GroupOfLinesStub } from "../../../../tests/stubs/GroupOfLinesStub";
import { FileDiffWidgetStub } from "../../../../tests/stubs/FileDiffWidgetStub";
import { FileLineHandleStub } from "../../../../tests/stubs/FileLineHandleStub";
import { FileLinesStateStub } from "../../../../tests/stubs/FileLinesStateStub";

import { SideBySideCommentPlaceholderBuilder } from "./side-by-side-comment-placeholder-builder";
import { SideBySidePlaceholderPositioner } from "./side-by-side-placeholder-positioner";

const left_code_mirror: Editor = {} as Editor;
const right_code_mirror: Editor = {} as Editor;

function getCommentPlaceholderBuilder(
    lines: FileLine[],
    group_of_lines: GroupOfLines[],
    lines_to_handles_map: Map<FileLine, SynchronizedLineHandles>
): BuildCommentPlaceholder {
    const state = FileLinesStateStub.build(lines, group_of_lines, lines_to_handles_map);

    return SideBySideCommentPlaceholderBuilder(
        left_code_mirror,
        right_code_mirror,
        state,
        SideBySidePlaceholderPositioner(state)
    );
}

describe("side-by-side-comment-placeholder", () => {
    describe("Given an unmoved line", () => {
        let unmoved_line: UnMovedFileLine;

        beforeEach(() => {
            unmoved_line = FileLineStub.buildUnMovedFileLine(1, 1);
        });

        it(`When it has one comment on the right side and nothing on the left side
            Then it will return some placeholder widget parameters with:
            - left code mirror and left handle (where the placeholder will go) as targets
            - the same height as the comments on the right side
            - an instruction to display the placeholder below the line.`, () => {
            const left_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
            const comment_placeholder_builder = getCommentPlaceholderBuilder(
                [unmoved_line],
                [GroupOfLinesStub.buildGroupOfUnMovedLines([unmoved_line])],
                new Map([
                    [
                        unmoved_line,
                        {
                            left_handle,
                            right_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                                FileDiffWidgetStub.buildInlineCommentWidget(20),
                            ]),
                        },
                    ],
                ])
            );

            const widget_params =
                comment_placeholder_builder.buildCommentsPlaceholderWidget(unmoved_line);

            expect(widget_params?.code_mirror).toBe(left_code_mirror);
            expect(widget_params?.handle).toBe(left_handle);
            expect(widget_params?.is_comment_placeholder).toBe(true);
            expect(widget_params?.widget_height).toBe(20);
            expect(widget_params?.display_above_line).toBe(false);
        });

        it(`When it has one comment on the left side and nothing on the right side
            Then it will return some placeholder widget parameters with:
            - the right code mirror and the right handle (where the placeholder will go) as targets
            - the same height as the comment on the left side
            - an instruction to display the placeholder below the line.`, () => {
            const right_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
            const comment_placeholder_builder = getCommentPlaceholderBuilder(
                [unmoved_line],
                [GroupOfLinesStub.buildGroupOfUnMovedLines([unmoved_line])],
                new Map([
                    [
                        unmoved_line,
                        {
                            left_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                                FileDiffWidgetStub.buildInlineCommentWidget(66),
                            ]),
                            right_handle,
                        },
                    ],
                ])
            );

            const widget_params =
                comment_placeholder_builder.buildCommentsPlaceholderWidget(unmoved_line);

            expect(widget_params?.code_mirror).toBe(right_code_mirror);
            expect(widget_params?.handle).toBe(right_handle);
            expect(widget_params?.is_comment_placeholder).toBe(true);
            expect(widget_params?.widget_height).toBe(66);
            expect(widget_params?.display_above_line).toBe(false);
        });

        it("When there is no widget on the left nor right side, then it returns null", () => {
            const comment_placeholder_builder = getCommentPlaceholderBuilder(
                [unmoved_line],
                [GroupOfLinesStub.buildGroupOfUnMovedLines([unmoved_line])],
                new Map([
                    [
                        unmoved_line,
                        {
                            left_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                            right_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                        },
                    ],
                ])
            );

            const widget_params =
                comment_placeholder_builder.buildCommentsPlaceholderWidget(unmoved_line);

            expect(widget_params).toBeNull();
        });
    });

    it(`Given the first line of an added file
        When the group of lines has comments
        Then it will return some placeholder widget parameters with:
        - the left code mirror and the left handle (where the placeholder will go) as targets
        - the height corresponding to the sum of the widgets heights in the group of added lines
        - an instruction to display the placeholder below the line.`, () => {
        const first_added_line = FileLineStub.buildAddedLine(1, 1);
        const second_added_line = FileLineStub.buildAddedLine(2, 2);
        const left_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
        const added_lines = [first_added_line, second_added_line];

        const comment_placeholder_builder = getCommentPlaceholderBuilder(
            added_lines,
            [GroupOfLinesStub.buildGroupOfAddedLines(added_lines)],
            new Map([
                [
                    first_added_line,
                    {
                        left_handle,
                        right_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                            FileDiffWidgetStub.buildInlineCommentWidget(48),
                        ]),
                    },
                ],
                [
                    second_added_line,
                    {
                        left_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                        right_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                            FileDiffWidgetStub.buildInlineCommentWidget(95),
                        ]),
                    },
                ],
            ])
        );

        const widget_params =
            comment_placeholder_builder.buildCommentsPlaceholderWidget(first_added_line);

        expect(widget_params?.code_mirror).toBe(left_code_mirror);
        expect(widget_params?.handle).toBe(left_handle);
        expect(widget_params?.is_comment_placeholder).toBe(true);
        expect(widget_params?.widget_height).toBe(143);
        expect(widget_params?.display_above_line).toBe(false);
    });

    it(`Given the first line of a deleted group of lines
        When the group of lines on the left code mirror has comments
        Then it will return some placeholder widget parameters with:
        - the right code mirror and the right handle (where the placeholder will go) as targets
        - the height corresponding to the sum of the widgets heights in the group of deleted lines
        - an instruction to display the placeholder above the line.`, () => {
        const first_deleted_line = FileLineStub.buildRemovedLine(5, 5);
        const second_deleted_line = FileLineStub.buildRemovedLine(6, 6);
        const first_right_line_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();

        const deleted_lines = [first_deleted_line, second_deleted_line];
        const comment_placeholder_builder = getCommentPlaceholderBuilder(
            deleted_lines,
            [GroupOfLinesStub.buildGroupOfRemovedLines(deleted_lines)],
            new Map([
                [
                    first_deleted_line,
                    {
                        left_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                            FileDiffWidgetStub.buildInlineCommentWidget(62),
                        ]),
                        right_handle: first_right_line_handle,
                    },
                ],
                [
                    second_deleted_line,
                    {
                        left_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                            FileDiffWidgetStub.buildInlineCommentWidget(42),
                        ]),
                        right_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                    },
                ],
            ])
        );

        const widget_params =
            comment_placeholder_builder.buildCommentsPlaceholderWidget(first_deleted_line);

        expect(widget_params?.code_mirror).toBe(right_code_mirror);
        expect(widget_params?.handle).toBe(first_right_line_handle);
        expect(widget_params?.is_comment_placeholder).toBe(true);
        expect(widget_params?.widget_height).toBe(104);
        expect(widget_params?.display_above_line).toBe(true);
    });

    it(`Given a file with an added and a deleted groups,
        And there was a comment in the deleted group,
        When we are treating the first line of the deleted group
        Then it will return some placeholder widget parameters with:
        - the right code mirror and the right handle (where the placeholder will go) as targets
        - the height corresponding to the sum of the widgets heights in the group of deleted lines
        - an instruction to display the placeholder above the line.

        And the two groups should have their prop "has_initial_comment_placeholder" set to "true"`, () => {
        const first_deleted_line = FileLineStub.buildRemovedLine(5, 5);
        const second_deleted_line = FileLineStub.buildRemovedLine(6, 6);
        const third_added_line = FileLineStub.buildAddedLine(7, 5);
        const fourth_added_line = FileLineStub.buildAddedLine(8, 6);

        const right_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
        const deleted_group = GroupOfLinesStub.buildGroupOfRemovedLines([
            first_deleted_line,
            second_deleted_line,
        ]);
        const added_group = GroupOfLinesStub.buildGroupOfAddedLines([
            third_added_line,
            fourth_added_line,
        ]);

        const comment_placeholder_builder = getCommentPlaceholderBuilder(
            [first_deleted_line, second_deleted_line, third_added_line, fourth_added_line],
            [deleted_group, added_group],
            new Map([
                [
                    first_deleted_line as FileLine,
                    {
                        left_handle: FileLineHandleStub.buildLineHandleWithWidgets([
                            FileDiffWidgetStub.buildInlineCommentWidget(89),
                        ]),
                        right_handle,
                    },
                ],
                [
                    second_deleted_line as FileLine,
                    {
                        left_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                        right_handle,
                    },
                ],
                [
                    third_added_line as FileLine,
                    {
                        left_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                        right_handle,
                    },
                ],
                [
                    fourth_added_line as FileLine,
                    {
                        left_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                        right_handle,
                    },
                ],
            ])
        );

        const widget_params =
            comment_placeholder_builder.buildCommentsPlaceholderWidget(first_deleted_line);

        expect(widget_params?.code_mirror).toBe(right_code_mirror);
        expect(widget_params?.handle).toBe(right_handle);
        expect(widget_params?.is_comment_placeholder).toBe(true);
        expect(widget_params?.widget_height).toBe(89);
        expect(widget_params?.display_above_line).toBe(true);

        expect(deleted_group.has_initial_comment_placeholder).toBe(true);
        expect(added_group.has_initial_comment_placeholder).toBe(true);
    });

    it(`Given the first line of a group that has already been handled
        Then the group will be skipped and it will return null`, () => {
        const first_deleted_line = FileLineStub.buildRemovedLine(5, 5);
        const second_added_line = FileLineStub.buildAddedLine(6, 5);
        const left_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
        const right_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
        const deleted_group = GroupOfLinesStub.buildGroupOfRemovedLines([first_deleted_line], true);
        const added_group = GroupOfLinesStub.buildGroupOfAddedLines([second_added_line], true);

        const comment_placeholder_builder = getCommentPlaceholderBuilder(
            [first_deleted_line, second_added_line],
            [deleted_group, added_group],
            new Map([
                [
                    first_deleted_line as FileLine,
                    {
                        left_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                        right_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                    },
                ],
                [
                    second_added_line as FileLine,
                    {
                        left_handle,
                        right_handle,
                    },
                ],
            ])
        );

        const widget_params =
            comment_placeholder_builder.buildCommentsPlaceholderWidget(second_added_line);

        expect(widget_params).toBeNull();
    });
});
