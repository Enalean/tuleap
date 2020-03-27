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

import { buildCodePlaceholderWidget } from "./side-by-side-code-placeholder-builder.js";
import { ADDED_GROUP, DELETED_GROUP } from "./side-by-side-line-grouper.js";
import * as side_by_side_lines_state from "./side-by-side-lines-state.js";

describe("side-by-side code placeholder builder", () => {
    const left_code_mirror = {};
    const right_code_mirror = {};
    let getLineHandles, getGroupOfLine, getGroupLines, hasNextLine, getNextLine;

    beforeEach(() => {
        getLineHandles = jest.spyOn(side_by_side_lines_state, "getLineHandles");
        getGroupOfLine = jest.spyOn(side_by_side_lines_state, "getGroupOfLine");
        getGroupLines = jest.spyOn(side_by_side_lines_state, "getGroupLines");
        hasNextLine = jest.spyOn(side_by_side_lines_state, "hasNextLine");
        getNextLine = jest.spyOn(side_by_side_lines_state, "getNextLine");
    });

    describe("buildCodePlaceholderWidget()", () => {
        describe("Deleted group -", () => {
            it("Given the first line of a deleted group, then it will return the right code mirror (where the line widget will go), the right line handle and the sum of the group's line handles' heights", () => {
                const first_deleted_line = { unidiff_offset: 2, old_offset: 2, new_offset: null };
                const second_deleted_line = { unidiff_offset: 3, old_offset: 3, new_offset: null };
                const first_right_handle = {};
                const first_left_handle = { height: 40 };
                const second_left_handle = { height: 20 };
                getLineHandles.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return {
                            left_handle: first_left_handle,
                            right_handle: first_right_handle,
                        };
                    }
                    if (value === second_deleted_line) {
                        return { left_handle: second_left_handle };
                    }
                    throw new Error(value);
                });
                const group = {
                    unidiff_offsets: [2, 3],
                    type: DELETED_GROUP,
                };
                getGroupOfLine.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return group;
                    }
                    throw new Error(value);
                });
                getGroupLines.mockImplementation((value) => {
                    if (value === group) {
                        return [first_deleted_line, second_deleted_line];
                    }
                    throw new Error(value);
                });

                const widget_params = buildCodePlaceholderWidget(
                    first_deleted_line,
                    group,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toEqual({
                    code_mirror: right_code_mirror,
                    handle: first_right_handle,
                    widget_height: 60,
                    display_above_line: true,
                    is_comment_placeholder: false,
                });
            });

            it("Given the deleted group starts at the beginning of the file, then the height of the first line will be subtracted from the height of the widget because there is always a first line, even when it's empty", () => {
                const first_deleted_line = { unidiff_offset: 1, old_offset: 1, new_offset: null };
                const second_deleted_line = { unidiff_offset: 2, old_offset: 2, new_offset: null };
                const first_right_handle = { height: 20 };
                const first_left_handle = { height: 20 };
                const second_left_handle = { height: 57 };
                getLineHandles.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return {
                            left_handle: first_left_handle,
                            right_handle: first_right_handle,
                        };
                    }
                    if (value === second_deleted_line) {
                        return { left_handle: second_left_handle };
                    }
                    throw new Error(value);
                });
                const group = {
                    unidiff_offsets: [1, 2],
                    type: DELETED_GROUP,
                };
                getGroupOfLine.mockReturnValue(group);
                getGroupLines.mockImplementation((value) => {
                    if (value === group) {
                        return [first_deleted_line, second_deleted_line];
                    }
                    throw new Error(value);
                });

                const widget_params = buildCodePlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toEqual({
                    code_mirror: right_code_mirror,
                    handle: first_right_handle,
                    widget_height: 57,
                    display_above_line: true,
                    is_comment_placeholder: false,
                });
            });
        });

        describe("Added group -", () => {
            it("Given the first line of an added group, then it will return the left code mirror (where the line widget will go), the left line handle and the sum of the group's line handles' heights", () => {
                const first_added_line = { unidiff_offset: 2, old_offset: null, new_offset: 2 };
                const second_added_line = { unidiff_offset: 3, old_offset: null, new_offset: 3 };
                const first_left_handle = {};
                const first_right_handle = { height: 20 };
                const second_right_handle = { height: 40 };
                getLineHandles.mockImplementation((value) => {
                    if (value === first_added_line) {
                        return {
                            left_handle: first_left_handle,
                            right_handle: first_right_handle,
                        };
                    }
                    if (value === second_added_line) {
                        return { right_handle: second_right_handle };
                    }
                    throw new Error(value);
                });
                const group = {
                    unidiff_offsets: [2, 3],
                    type: ADDED_GROUP,
                };
                getGroupOfLine.mockImplementation((value) => {
                    if (value === first_added_line) {
                        return group;
                    }
                    throw new Error(value);
                });
                getGroupLines.mockImplementation((value) => {
                    if (value === group) {
                        return [first_added_line, second_added_line];
                    }
                    throw new Error(value);
                });

                const widget_params = buildCodePlaceholderWidget(
                    first_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toEqual({
                    code_mirror: left_code_mirror,
                    handle: first_left_handle,
                    widget_height: 60,
                    display_above_line: false,
                    is_comment_placeholder: false,
                });
            });

            it("Given the added group starts at the beginning of the file, then the height of the first line will be subtracted from the height of the widget because there is always a first line, even when it's empty", () => {
                const first_added_line = { unidiff_offset: 1, old_offset: null, new_offset: 1 };
                const second_added_line = { unidiff_offset: 2, old_offset: null, new_offset: 2 };
                const first_left_handle = { height: 20 };
                const first_right_handle = { height: 57 };
                const second_right_handle = { height: 20 };
                getLineHandles.mockImplementation((value) => {
                    if (value === first_added_line) {
                        return {
                            left_handle: first_left_handle,
                            right_handle: first_right_handle,
                        };
                    }
                    if (value === second_added_line) {
                        return { right_handle: second_right_handle };
                    }
                    throw new Error(value);
                });
                const group = {
                    unidiff_offsets: [2, 3],
                    type: ADDED_GROUP,
                };
                getGroupOfLine.mockReturnValue(group);
                getGroupLines.mockImplementation((value) => {
                    if (value === group) {
                        return [first_added_line, second_added_line];
                    }
                    throw new Error(value);
                });

                const widget_params = buildCodePlaceholderWidget(
                    first_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toEqual({
                    code_mirror: left_code_mirror,
                    handle: first_left_handle,
                    widget_height: 57,
                    display_above_line: false,
                    is_comment_placeholder: false,
                });
            });
        });

        describe("First line modified -", () => {
            it("Given the first line is modified (it will appear as deleted and then added), then the height of the first line will not be subtracted from the height of the widget", () => {
                const first_deleted_line = { unidiff_offset: 1, old_offset: 1, new_offset: null };
                const second_added_line = { unidiff_offset: 2, old_offset: null, new_offset: 1 };
                hasNextLine.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return true;
                    }
                    throw new Error(value);
                });
                getNextLine.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return second_added_line;
                    }
                    throw new Error(value);
                });
                const left_handle = { height: 40 };
                const right_handle = { height: 40 };
                getLineHandles.mockImplementation((value) => {
                    if (value === first_deleted_line || value === second_added_line) {
                        return {
                            left_handle,
                            right_handle,
                        };
                    }
                    throw new Error(value);
                });
                const deleted_group = {
                    unidiff_offsets: [1],
                    type: DELETED_GROUP,
                };
                const added_group = {
                    unidiff_offsets: [2],
                    type: ADDED_GROUP,
                };
                getGroupOfLine.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return deleted_group;
                    }
                    if (value === second_added_line) {
                        return added_group;
                    }
                    throw new Error(value);
                });
                getGroupLines.mockImplementation((value) => {
                    if (value === deleted_group) {
                        return [first_deleted_line];
                    }
                    if (value === added_group) {
                        return [second_added_line];
                    }
                    throw new Error(value);
                });

                const widget_params = buildCodePlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toEqual({
                    code_mirror: right_code_mirror,
                    handle: right_handle,
                    widget_height: 40,
                    display_above_line: true,
                    is_comment_placeholder: false,
                });
            });
        });
    });
});
