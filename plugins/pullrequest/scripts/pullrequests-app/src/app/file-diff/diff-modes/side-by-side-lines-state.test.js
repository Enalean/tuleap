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

import {
    initDataAndCodeMirrors,
    getCommentLine,
    getGroupLines,
    getLineOfHandle,
} from "./side-by-side-lines-state.js";
import * as side_by_side_line_grouper from "./side-by-side-line-grouper.js";
import * as side_by_side_line_mapper from "./side-by-side-line-mapper.js";
import { ADDED_GROUP, DELETED_GROUP, UNMOVED_GROUP } from "./side-by-side-line-grouper";

describe("side-by-side lines state", () => {
    let buildLineGroups, buildLineToLineHandlesMap, left_code_mirror, right_code_mirror;

    beforeEach(() => {
        buildLineGroups = jest
            .spyOn(side_by_side_line_grouper, "buildLineGroups")
            .mockImplementation(() => ({
                first_line_to_group_map: new Map(),
                line_to_group_map: new Map(),
            }));
        buildLineToLineHandlesMap = jest.spyOn(
            side_by_side_line_mapper,
            "buildLineToLineHandlesMap"
        );

        left_code_mirror = buildCodeMirrorSpy();
        right_code_mirror = buildCodeMirrorSpy();
    });

    describe("initDataAndCodeMirrors()", () => {
        it("Given diff lines, the left and right code mirrors, then it will store the lines, set the left and right code mirror content and build line maps", () => {
            buildLineToLineHandlesMap.mockImplementation(() => {});
            const lines = [
                { old_offset: 1, new_offset: 1 },
                { old_offset: 2, new_offset: null },
                { old_offset: null, new_offset: 2 },
            ];

            initDataAndCodeMirrors(lines, left_code_mirror, right_code_mirror);

            expect(buildLineGroups).toHaveBeenCalled();
            expect(left_code_mirror.setValue).toHaveBeenCalled();
            expect(right_code_mirror.setValue).toHaveBeenCalled();
            expect(buildLineToLineHandlesMap).toHaveBeenCalled();
        });
    });

    describe("getCommentLine()", () => {
        it("Given a comment, then it will return its line", () => {
            const comment = {
                unidiff_offset: 2,
            };
            const first_line = { unidiff_offset: 1 };
            const second_line = { unidiff_offset: 2 };
            const lines = [first_line, second_line];
            initDataAndCodeMirrors(lines, left_code_mirror, right_code_mirror);

            expect(getCommentLine(comment)).toBe(second_line);
        });
    });

    describe("getGroupLines()", () => {
        it("Given a group, then it will return the group's lines", () => {
            const group = {
                unidiff_offsets: [2, 3],
            };
            const first_line = { unidiff_offset: 1 };
            const second_line = { unidiff_offset: 2 };
            const third_line = { unidiff_offset: 3 };
            const lines = [first_line, second_line, third_line];
            initDataAndCodeMirrors(lines, left_code_mirror, right_code_mirror);

            const group_lines = getGroupLines(group);

            expect(group_lines).toEqual([second_line, third_line]);
        });
    });

    describe("getLineOfHandle()", () => {
        it("Given handles matching an unmoved line, then it will return the unmoved line", () => {
            const line = { unidiff_offset: 1, old_offset: 1, new_offset: 1 };
            const left_handle = {};
            const right_handle = {};
            const unmoved_group = { type: UNMOVED_GROUP };
            buildLineGroups.mockReturnValue({
                line_to_group_map: new Map([[line.unidiff_offset, unmoved_group]]),
            });
            buildLineToLineHandlesMap.mockReturnValue(
                new Map([
                    [
                        line,
                        {
                            left_handle,
                            right_handle,
                        },
                    ],
                ])
            );
            initDataAndCodeMirrors([line], left_code_mirror, right_code_mirror);

            expect(getLineOfHandle(left_handle)).toBe(line);
            expect(getLineOfHandle(right_handle)).toBe(line);
        });

        it("Given the left handle of an added line, then it will return the opposite line (not the added line)", () => {
            const added_line = { unidiff_offset: 1, old_offset: null, new_offset: 1 };
            const opposite_line = { unidiff_offset: 2, old_offset: 1, new_offset: 2 };
            const added_handle = {};
            const opposite_left_handle = {};
            const opposite_right_handle = {};
            const added_group = { type: ADDED_GROUP };
            const unmoved_group = { type: UNMOVED_GROUP };
            buildLineGroups.mockReturnValue({
                line_to_group_map: new Map([
                    [added_line.unidiff_offset, added_group],
                    [opposite_line.unidiff_offset, unmoved_group],
                ]),
            });
            buildLineToLineHandlesMap.mockReturnValue(
                new Map([
                    [
                        added_line,
                        {
                            left_handle: opposite_left_handle,
                            right_handle: added_handle,
                        },
                    ],
                    [
                        opposite_line,
                        {
                            left_handle: opposite_left_handle,
                            right_handle: opposite_right_handle,
                        },
                    ],
                ])
            );
            initDataAndCodeMirrors(
                [added_line, opposite_line],
                left_code_mirror,
                right_code_mirror
            );

            expect(getLineOfHandle(added_handle)).toBe(added_line);
            expect(getLineOfHandle(opposite_left_handle)).toBe(opposite_line);
        });

        it("Given the right handle of a deleted line, then it will return the opposite line (not the deleted line)", () => {
            const opposite_line = { unidiff_offset: 1, old_offset: null, new_offset: 1 };
            const deleted_line = { unidiff_offset: 2, old_offset: 1, new_offset: null };
            const opposite_left_handle = {};
            const opposite_right_handle = {};
            const deleted_handle = { a: "a" };
            const added_group = { type: ADDED_GROUP };
            const deleted_group = { type: DELETED_GROUP };
            buildLineGroups.mockReturnValue({
                line_to_group_map: new Map([
                    [opposite_line.unidiff_offset, added_group],
                    [deleted_line.unidiff_offset, deleted_group],
                ]),
            });
            buildLineToLineHandlesMap.mockReturnValue(
                new Map([
                    [
                        opposite_line,
                        {
                            left_handle: opposite_left_handle,
                            right_handle: opposite_right_handle,
                        },
                    ],
                    [
                        deleted_line,
                        {
                            left_handle: deleted_handle,
                            right_handle: opposite_right_handle,
                        },
                    ],
                ])
            );
            initDataAndCodeMirrors(
                [opposite_line, deleted_line],
                left_code_mirror,
                right_code_mirror
            );

            expect(getLineOfHandle(deleted_handle)).toBe(deleted_line);
            expect(getLineOfHandle(opposite_right_handle)).toBe(opposite_line);
        });
    });
});

function buildCodeMirrorSpy() {
    return {
        getLineHandle: jest.fn(),
        setValue: jest.fn(),
    };
}
