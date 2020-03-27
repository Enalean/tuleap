/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { buildLineToLineHandlesMap } from "./side-by-side-line-mapper.js";
import { ADDED_GROUP, DELETED_GROUP } from "./side-by-side-line-grouper.js";

describe("side-by-side line mapper", () => {
    describe("buildLineToLineHandlesMap()", () => {
        let left_code_mirror, right_code_mirror;
        beforeEach(() => {
            left_code_mirror = buildCodeMirrorSpy();
            right_code_mirror = buildCodeMirrorSpy();

            left_code_mirror.getLineHandle.mockReturnValue("handle");
            right_code_mirror.getLineHandle.mockReturnValue("handle");
        });

        describe("Unmoved lines -", () => {
            it("Given diff lines, a map from line to group and the left and right code mirrors, then it will return a map from line to left-side LineHandle and right-side LineHandle for unmoved lines", () => {
                const first_unmoved_line = { unidiff_offset: 1, old_offset: 1, new_offset: 1 };
                const second_unmoved_line = { unidiff_offset: 2, old_offset: 2, new_offset: 2 };
                const lines = [first_unmoved_line, second_unmoved_line];

                const first_line_left_handle = {};
                const first_line_right_handle = {};
                const second_line_left_handle = {};
                const second_line_right_handle = {};
                left_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0) {
                        return first_line_left_handle;
                    }
                    if (value === 1) {
                        return second_line_left_handle;
                    }
                    throw new Error(value);
                });
                right_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0) {
                        return first_line_right_handle;
                    }
                    if (value === 1) {
                        return second_line_right_handle;
                    }
                    throw new Error(value);
                });

                const map = buildLineToLineHandlesMap(
                    lines,
                    null,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(map.get(first_unmoved_line)).toEqual({
                    left_handle: first_line_left_handle,
                    right_handle: first_line_right_handle,
                });
                expect(map.get(second_unmoved_line)).toEqual({
                    left_handle: second_line_left_handle,
                    right_handle: second_line_right_handle,
                });
            });
        });

        describe("Added lines -", () => {
            it("will return a map from line to right-side LineHandle and on the left-side to the first line before the added group so that I can place a line widget there", () => {
                const first_line = {
                    unidiff_offset: 1,
                    old_offset: 1,
                    new_offset: 1,
                };
                const first_added_line = { unidiff_offset: 2, old_offset: null, new_offset: 2 };
                const second_added_line = { unidiff_offset: 3, old_offset: null, new_offset: 3 };
                const lines = [first_line, first_added_line, second_added_line];

                const first_line_before_group_left_handle = {};
                const first_added_line_right_handle = {};
                const second_added_line_right_handle = {};
                left_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0) {
                        return first_line_before_group_left_handle;
                    }
                    throw new Error(value);
                });
                right_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0) {
                        return {};
                    }
                    if (value === 1) {
                        return first_added_line_right_handle;
                    }
                    if (value === 2) {
                        return second_added_line_right_handle;
                    }
                    throw new Error(value);
                });

                const added_group = {
                    unidiff_offsets: [2, 3],
                    type: ADDED_GROUP,
                };
                const line_to_group_map = new Map([
                    [2, added_group],
                    [3, added_group],
                ]);

                const map = buildLineToLineHandlesMap(
                    lines,
                    line_to_group_map,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(map.get(first_added_line)).toEqual({
                    left_handle: first_line_before_group_left_handle,
                    right_handle: first_added_line_right_handle,
                });
                expect(map.get(second_added_line)).toEqual({
                    left_handle: first_line_before_group_left_handle,
                    right_handle: second_added_line_right_handle,
                });
            });

            it("Given the added group starts at the beginning of the file, then the left-side LineHandle will be at the start of the file (line 0)", () => {
                const first_added_line = { unidiff_offset: 1, old_offset: null, new_offset: 1 };
                const second_added_line = { unidiff_offset: 2, old_offset: null, new_offset: 2 };
                const lines = [first_added_line, second_added_line];

                const first_line_left_handle = {};
                const first_line_right_handle = {};
                const second_line_right_handle = {};
                left_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0) {
                        return first_line_left_handle;
                    }
                    throw new Error(value);
                });
                right_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0) {
                        return first_line_right_handle;
                    }
                    if (value === 1) {
                        return second_line_right_handle;
                    }
                    throw new Error(value);
                });

                const added_group = {
                    unidiff_offsets: [1, 2],
                    type: ADDED_GROUP,
                };
                const line_to_group_map = new Map([
                    [1, added_group],
                    [2, added_group],
                ]);

                const map = buildLineToLineHandlesMap(
                    lines,
                    line_to_group_map,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(map.get(first_added_line)).toEqual({
                    left_handle: first_line_left_handle,
                    right_handle: first_line_right_handle,
                });
                expect(map.get(second_added_line)).toEqual({
                    left_handle: first_line_left_handle,
                    right_handle: second_line_right_handle,
                });
            });
        });

        describe("Deleted lines -", () => {
            it("will return a map from line to left-side LineHandle and on the right-side to the first line after the deleted group so that I can place a line widget there", () => {
                const first_line = { unidiff_offset: 1, old_offset: 1, new_offset: 1 };
                const second_deleted_line = { unidiff_offset: 2, old_offset: 2, new_offset: null };
                const third_deleted_line = { unidiff_offset: 3, old_offset: 3, new_offset: null };
                const fourth_line = {
                    unidiff_offset: 4,
                    old_offset: 4,
                    new_offset: 2,
                };
                const lines = [first_line, second_deleted_line, third_deleted_line, fourth_line];

                const second_deleted_line_left_handle = {};
                const third_deleted_line_left_handle = {};
                const first_line_after_group_right_handle = {};

                left_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0 || value === 3) {
                        return {};
                    }
                    if (value === 1) {
                        return second_deleted_line_left_handle;
                    }
                    if (value === 2) {
                        return third_deleted_line_left_handle;
                    }
                    throw new Error(value);
                });
                right_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0) {
                        return {};
                    }
                    if (value === 1) {
                        return first_line_after_group_right_handle;
                    }
                    throw new Error(value);
                });

                const deleted_group = {
                    unidiff_offsets: [2, 3],
                    type: DELETED_GROUP,
                };
                const line_to_group_map = new Map([
                    [2, deleted_group],
                    [3, deleted_group],
                ]);

                const map = buildLineToLineHandlesMap(
                    lines,
                    line_to_group_map,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(map.get(second_deleted_line)).toEqual({
                    left_handle: second_deleted_line_left_handle,
                    right_handle: first_line_after_group_right_handle,
                });
                expect(map.get(third_deleted_line)).toEqual({
                    left_handle: third_deleted_line_left_handle,
                    right_handle: first_line_after_group_right_handle,
                });
            });

            it("Given the deleted group is at the end of the file, then the right-side LineHandle will be at the last line of the previous group", () => {
                const first_deleted_line = { unidiff_offset: 1, old_offset: 1, new_offset: null };
                const second_deleted_line = { unidiff_offset: 2, old_offset: 2, new_offset: null };
                const third_unmoved_line = { unidiff_offset: 3, old_offset: 3, new_offset: 1 };
                const lines = [first_deleted_line, second_deleted_line, third_unmoved_line];

                const first_line_left_handle = {};
                const second_line_left_handle = {};
                const third_line_right_handle = {};
                left_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0) {
                        return first_line_left_handle;
                    }
                    if (value === 1) {
                        return second_line_left_handle;
                    }
                    if (value === 2) {
                        return {};
                    }
                    throw new Error(value);
                });
                right_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0) {
                        return third_line_right_handle;
                    }
                    throw new Error(value);
                });

                const deleted_group = {
                    unidiff_offsets: [1, 2],
                    type: DELETED_GROUP,
                };
                const line_to_group_map = new Map([
                    [1, deleted_group],
                    [2, deleted_group],
                ]);

                const map = buildLineToLineHandlesMap(
                    lines,
                    line_to_group_map,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(map.get(first_deleted_line)).toEqual({
                    left_handle: first_line_left_handle,
                    right_handle: third_line_right_handle,
                });
                expect(map.get(second_deleted_line)).toEqual({
                    left_handle: second_line_left_handle,
                    right_handle: third_line_right_handle,
                });
            });

            it("Given we're dealing with a deleted file, then the right-side LineHandle will be at the start of the file (line 0)", () => {
                const first_deleted_line = { unidiff_offset: 1, old_offset: 1, new_offset: null };
                const second_deleted_line = { unidiff_offset: 2, old_offset: 2, new_offset: null };
                const lines = [first_deleted_line, second_deleted_line];

                const first_line_left_handle = {};
                const first_line_right_handle = {};
                const second_line_left_handle = {};
                left_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0) {
                        return first_line_left_handle;
                    }
                    if (value === 1) {
                        return second_line_left_handle;
                    }
                    throw new Error(value);
                });
                right_code_mirror.getLineHandle.mockImplementation((value) => {
                    if (value === 0) {
                        return first_line_right_handle;
                    }
                    throw new Error(value);
                });

                const deleted_group = {
                    unidiff_offsets: [1, 2],
                    type: DELETED_GROUP,
                };
                const line_to_group_map = new Map([
                    [1, deleted_group],
                    [2, deleted_group],
                ]);

                const map = buildLineToLineHandlesMap(
                    lines,
                    line_to_group_map,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(map.get(first_deleted_line)).toEqual({
                    left_handle: first_line_right_handle,
                    right_handle: first_line_left_handle,
                });
                expect(map.get(second_deleted_line)).toEqual({
                    left_handle: second_line_left_handle,
                    right_handle: first_line_left_handle,
                });
            });
        });
    });
});

function buildCodeMirrorSpy() {
    return {
        getLineHandle: jest.fn(),
    };
}
