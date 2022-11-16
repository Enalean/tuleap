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

import { buildCommentsPlaceholderWidget } from "./side-by-side-comment-placeholder-builder.js";
import { FileLineStub } from "../../../../tests/stubs/FileLineStub";
import { GroupOfLinesStub } from "../../../../tests/stubs/GroupOfLinesStub";
import * as side_by_side_lines_state from "./side-by-side-lines-state.js";
import { FileDiffWidgetStub } from "../../../../tests/stubs/FileDiffWidgetStub";
import { FileLineHandleStub } from "../../../../tests/stubs/FileLineHandleStub";

describe("side-by-side widget builder", () => {
    const left_code_mirror = {};
    const right_code_mirror = {};
    let getGroupLines, getLineHandles, getGroupOfLine, getLineOfHandle;

    beforeEach(() => {
        getGroupLines = jest.spyOn(side_by_side_lines_state, "getGroupLines");
        getLineHandles = jest.spyOn(side_by_side_lines_state, "getLineHandles");
        getGroupOfLine = jest.spyOn(side_by_side_lines_state, "getGroupOfLine");
        getLineOfHandle = jest.spyOn(side_by_side_lines_state, "getLineOfHandle");
    });

    describe("buildCommentsPlaceholderWidget()", () => {
        describe("Given an unmoved line,", () => {
            const unmoved_line = FileLineStub.buildUnMovedFileLine(1, 1);
            describe("when it has a comment on the right side", () => {
                const left_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
                const right_handle = FileLineHandleStub.buildLineHandleWithWidgets([
                    FileDiffWidgetStub.buildInlineCommentWidget(20),
                ]);
                beforeEach(() => {
                    getLineHandles.mockImplementation((value) => {
                        if (value === unmoved_line) {
                            return { left_handle, right_handle };
                        }
                        throw new Error(value);
                    });
                });

                it("then it will return the left code mirror and left handle (where the placeholder will go)", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.code_mirror).toBe(left_code_mirror);
                    expect(widget_params.handle).toBe(left_handle);
                    expect(widget_params.is_comment_placeholder).toBe(true);
                });

                it("then it computes the widget height as the sum of comments on the right side", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.widget_height).toBe(20);
                });

                it("then it sets the widget below the line", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.display_above_line).toBe(false);
                });
            });

            describe("when it has a comment on the left side", () => {
                const right_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
                const left_handle = FileLineHandleStub.buildLineHandleWithWidgets([
                    FileDiffWidgetStub.buildInlineCommentWidget(66),
                ]);

                beforeEach(() => {
                    getLineHandles.mockImplementation((value) => {
                        if (value === unmoved_line) {
                            return { left_handle, right_handle };
                        }
                        throw new Error(value);
                    });
                    getLineOfHandle.mockImplementation((value) => {
                        if (value === left_handle) {
                            return unmoved_line;
                        }
                        throw new Error(value);
                    });
                    getLineOfHandle.mockImplementation((value) => {
                        if (value === right_handle) {
                            return unmoved_line;
                        }
                    });
                });

                it("then it will return the right code mirror and the right handle (where the placeholder will go)", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.code_mirror).toBe(right_code_mirror);
                    expect(widget_params.handle).toBe(right_handle);
                    expect(widget_params.is_comment_placeholder).toBe(true);
                });

                it("then it will compute the widget height as the sum of comments on the left side", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.widget_height).toBe(66);
                });

                it("then it will set the widget below the line", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.display_above_line).toBe(false);
                });
            });

            it("When it has no comment, then widget parameters will be null", () => {
                getLineHandles.mockImplementation((value) => {
                    if (value === unmoved_line) {
                        return {
                            left_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                            right_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                        };
                    }
                });

                const widget_params = buildCommentsPlaceholderWidget(
                    unmoved_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toBeNull();
            });
        });

        describe("Given the first line of an added file and the group lines had comments", () => {
            const first_added_line = FileLineStub.buildAddedLine(1, 1);
            const second_added_line = FileLineStub.buildAddedLine(2, 2);
            const left_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
            const first_right_handle = FileLineHandleStub.buildLineHandleWithWidgets([
                FileDiffWidgetStub.buildInlineCommentWidget(48),
            ]);
            const second_right_handle = FileLineHandleStub.buildLineHandleWithWidgets([
                FileDiffWidgetStub.buildInlineCommentWidget(95),
            ]);
            const group = GroupOfLinesStub.buildGroupOfAddedLines([
                first_added_line,
                second_added_line,
            ]);

            beforeEach(() => {
                getLineOfHandle.mockImplementation((value) => {
                    if (value === left_handle) {
                        return null;
                    }
                    if (value === first_right_handle) {
                        return first_added_line;
                    }
                    throw new Error(value);
                });
                getLineHandles.mockImplementation((value) => {
                    if (value === first_added_line) {
                        return { left_handle, right_handle: first_right_handle };
                    }
                    if (value === second_added_line) {
                        return { left_handle, right_handle: second_right_handle };
                    }
                    throw new Error(value);
                });
                getGroupOfLine.mockImplementation((value) => {
                    if (value === first_added_line) {
                        return group;
                    }
                    throw new Error(value);
                });
                getGroupLines.mockImplementation((value) => {
                    if (group === value) {
                        return [first_added_line, second_added_line];
                    }
                    throw new Error(value);
                });
            });

            it("then it will return the left code mirror and the left handle (where the placeholder will go)", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.code_mirror).toBe(left_code_mirror);
                expect(widget_params.handle).toBe(left_handle);
                expect(widget_params.is_comment_placeholder).toBe(true);
            });

            it("then it will compute the widget height as the sum of comments on the lines of the group", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.widget_height).toBe(143);
            });

            it("then it will set the widget below the line", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.display_above_line).toBe(false);
            });
        });

        describe("Given the first line of a deleted file and the group lines had comments", () => {
            const first_deleted_line = FileLineStub.buildRemovedLine(55, 55);
            const second_deleted_line = FileLineStub.buildRemovedLine(56, 56);
            const first_left_handle = FileLineHandleStub.buildLineHandleWithWidgets([
                FileDiffWidgetStub.buildInlineCommentWidget(62),
            ]);
            const second_left_handle = FileLineHandleStub.buildLineHandleWithWidgets([
                FileDiffWidgetStub.buildInlineCommentWidget(42),
            ]);
            const right_handle = {};
            const group = GroupOfLinesStub.buildGroupOfRemovedLines([
                first_deleted_line,
                second_deleted_line,
            ]);

            beforeEach(() => {
                getLineHandles.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return { left_handle: first_left_handle, right_handle };
                    }
                    if (value === second_deleted_line) {
                        return { left_handle: second_left_handle, right_handle };
                    }
                    throw new Error(value);
                });
                getLineOfHandle.mockImplementation((value) => {
                    if (value === first_left_handle) {
                        return first_deleted_line;
                    }
                    if (value === right_handle) {
                        return null;
                    }
                    throw new Error(value);
                });
                getGroupOfLine.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return group;
                    }
                });
                getGroupLines.mockImplementation((value) => {
                    if (value === group) {
                        return [first_deleted_line, second_deleted_line];
                    }
                    throw new Error(value);
                });
            });

            it("then it will return the right code mirror and the right handle (where the placeholder will go)", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.code_mirror).toBe(right_code_mirror);
                expect(widget_params.handle).toBe(right_handle);
                expect(widget_params.is_comment_placeholder).toBe(true);
            });

            it("then it will compute the widget height as the sum of comments on the lines of the group", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.widget_height).toBe(104);
            });

            it("then it will set the widget above the line", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.display_above_line).toBe(true);
            });
        });

        describe("Given a file with an added and a deleted groups, and there was a comment in the deleted group, when I am treating the first line of the deleted group", () => {
            const first_deleted_line = FileLineStub.buildRemovedLine(5, 5);
            const second_deleted_line = FileLineStub.buildRemovedLine(6, 6);
            const third_added_line = FileLineStub.buildAddedLine(7, 5);
            const fourth_added_line = FileLineStub.buildAddedLine(8, 6);

            const left_handle = FileLineHandleStub.buildLineHandleWithWidgets([
                FileDiffWidgetStub.buildInlineCommentWidget(89),
            ]);
            const right_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
            let deleted_group, added_group;

            beforeEach(() => {
                deleted_group = GroupOfLinesStub.buildGroupOfRemovedLines([
                    first_deleted_line,
                    second_deleted_line,
                ]);
                added_group = GroupOfLinesStub.buildGroupOfAddedLines([
                    third_added_line,
                    fourth_added_line,
                ]);
                getLineHandles.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return { left_handle, right_handle };
                    }
                    return {
                        left_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                        right_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                    };
                });
                getLineOfHandle.mockImplementation((value) => {
                    if (value === left_handle) {
                        return first_deleted_line;
                    }
                    if (value === right_handle) {
                        return third_added_line;
                    }
                    throw new Error(value);
                });
                getGroupOfLine.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return deleted_group;
                    }
                    if (value === third_added_line) {
                        return added_group;
                    }
                    throw new Error(value);
                });
                getGroupLines.mockImplementation((value) => {
                    if (value === deleted_group) {
                        return [first_deleted_line, second_deleted_line];
                    }
                    if (value === added_group) {
                        return [third_added_line, fourth_added_line];
                    }
                    throw new Error(value);
                });
            });

            it("then it will return the right code mirror and the right handle", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.code_mirror).toBe(right_code_mirror);
                expect(widget_params.handle).toBe(right_handle);
                expect(widget_params.is_comment_placeholder).toBe(true);
            });

            it("then it will compute the widget height as the sum of comments on the lines of the group", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.widget_height).toBe(89);
            });

            it("then it will set the widget above the line", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.display_above_line).toBe(true);
            });

            it("then it will mark both added and deleted groups as handled", () => {
                buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(deleted_group.has_initial_comment_placeholder).toBe(true);
                expect(added_group.has_initial_comment_placeholder).toBe(true);
            });
        });

        describe("Given the first line of a group that has already been handled", () => {
            const first_deleted_line = FileLineStub.buildRemovedLine(5, 5);
            const second_added_line = FileLineStub.buildAddedLine(6, 5);
            const left_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
            const right_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
            const deleted_group = GroupOfLinesStub.buildGroupOfRemovedLines(
                [first_deleted_line],
                true
            );
            const added_group = GroupOfLinesStub.buildGroupOfAddedLines([second_added_line], true);

            beforeEach(() => {
                getLineHandles.mockImplementation((value) => {
                    if (value === second_added_line) {
                        return { left_handle, right_handle };
                    }
                    throw new Error(value);
                });
                getLineOfHandle.mockImplementation((value) => {
                    if (value === left_handle) {
                        return first_deleted_line;
                    }
                    if (value === right_handle) {
                        return second_added_line;
                    }
                    throw new Error(value);
                });
                getGroupOfLine.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return deleted_group;
                    }
                    if (value === second_added_line) {
                        return added_group;
                    }
                    throw new Error(value);
                });
            });

            it("then the group will be skipped and it will return null", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    second_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toBeNull();
            });
        });
    });
});
