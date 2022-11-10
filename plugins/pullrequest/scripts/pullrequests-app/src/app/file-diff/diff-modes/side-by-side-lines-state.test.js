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

import { GroupSideBySideLinesStub } from "../../../../tests/stubs/GroupSideBySideLinesStub";
import { FileLineStub } from "../../../../tests/stubs/FileLineStub";
import { GroupOfLinesStub } from "../../../../tests/stubs/GroupOfLinesStub";
import { MapSideBySideLinesStub } from "../../../../tests/stubs/MapSideBySideLinesStub";

describe("side-by-side lines state", () => {
    let side_by_side_line_grouper, side_by_side_line_mapper, left_code_mirror, right_code_mirror;

    beforeEach(() => {
        side_by_side_line_grouper = GroupSideBySideLinesStub();
        side_by_side_line_mapper = MapSideBySideLinesStub();

        left_code_mirror = buildCodeMirrorSpy();
        right_code_mirror = buildCodeMirrorSpy();
    });

    describe("initDataAndCodeMirrors()", () => {
        it("Given diff lines, the left and right code mirrors, then it will store the lines, set the left and right code mirror content and build line maps", () => {
            const lines = [
                FileLineStub.buildUnMovedFileLine(1, 1),
                FileLineStub.buildRemovedLine(2, 2),
                FileLineStub.buildAddedLine(3, 2),
            ];

            initDataAndCodeMirrors(
                lines,
                left_code_mirror,
                right_code_mirror,
                side_by_side_line_grouper.withEmptyLineToGroupMap(),
                side_by_side_line_mapper.withSideBySideLineMap(new Map())
            );

            expect(side_by_side_line_grouper.hasBuiltLineToGroupMap()).toBe(true);
            expect(side_by_side_line_grouper.hasBuildFirstLineToGroupMap()).toBe(true);
            expect(left_code_mirror.setValue).toHaveBeenCalled();
            expect(right_code_mirror.setValue).toHaveBeenCalled();
            expect(side_by_side_line_mapper.getNbCalls()).toBe(1);
        });
    });

    describe("getCommentLine()", () => {
        it("Given a comment, then it will return its line", () => {
            const comment = {
                unidiff_offset: 2,
            };
            const first_line = FileLineStub.buildUnMovedFileLine(1, 1);
            const second_line = FileLineStub.buildUnMovedFileLine(2, 2);
            const lines = [first_line, second_line];
            initDataAndCodeMirrors(
                lines,
                left_code_mirror,
                right_code_mirror,
                side_by_side_line_grouper.withEmptyLineToGroupMap(),
                side_by_side_line_mapper.withSideBySideLineMap(new Map())
            );

            expect(getCommentLine(comment)).toBe(second_line);
        });
    });

    describe("getGroupLines()", () => {
        it("Given a group, then it will return the group's lines", () => {
            const first_line = FileLineStub.buildUnMovedFileLine(1, 1);
            const second_line = FileLineStub.buildUnMovedFileLine(2, 2);
            const third_line = FileLineStub.buildRemovedLine(3, 3);

            const unmoved_lines = GroupOfLinesStub.buildGroupOfUnMovedLines([
                first_line,
                second_line,
            ]);
            const removed_lines = GroupOfLinesStub.buildGroupOfUnMovedLines([third_line]);

            initDataAndCodeMirrors(
                [first_line, second_line, third_line],
                left_code_mirror,
                right_code_mirror,
                side_by_side_line_grouper.withGroupsOfLines([unmoved_lines, removed_lines]),
                side_by_side_line_mapper.withSideBySideLineMap(new Map())
            );

            expect(getGroupLines(unmoved_lines)).toStrictEqual([first_line, second_line]);
            expect(getGroupLines(removed_lines)).toStrictEqual([third_line]);
        });
    });

    describe("getLineOfHandle()", () => {
        it("Given handles matching an unmoved line, then it will return the unmoved line", () => {
            const unmoved_line = FileLineStub.buildUnMovedFileLine(1, 1);
            const left_handle = {};
            const right_handle = {};
            const unmoved_group = GroupOfLinesStub.buildGroupOfUnMovedLines([unmoved_line]);

            initDataAndCodeMirrors(
                [unmoved_line],
                left_code_mirror,
                right_code_mirror,
                side_by_side_line_grouper.withGroupsOfLines([unmoved_group]),
                side_by_side_line_mapper.withSideBySideLineMap(
                    new Map([
                        [
                            unmoved_line,
                            {
                                left_handle,
                                right_handle,
                            },
                        ],
                    ])
                )
            );

            expect(getLineOfHandle(left_handle)).toBe(unmoved_line);
            expect(getLineOfHandle(right_handle)).toBe(unmoved_line);
        });

        it("Given the left handle of an added line, then it will return the opposite line (not the added line)", () => {
            const added_line = FileLineStub.buildAddedLine(1, 1);
            const opposite_line = FileLineStub.buildUnMovedFileLine(2, 1);
            const added_handle = {};
            const opposite_left_handle = {};
            const opposite_right_handle = {};
            const added_group = GroupOfLinesStub.buildGroupOfAddedLines([added_line]);
            const unmoved_group = GroupOfLinesStub.buildGroupOfUnMovedLines([opposite_line]);

            initDataAndCodeMirrors(
                [added_line, opposite_line],
                left_code_mirror,
                right_code_mirror,
                side_by_side_line_grouper.withGroupsOfLines([added_group, unmoved_group]),
                side_by_side_line_mapper.withSideBySideLineMap(
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
                )
            );

            expect(getLineOfHandle(added_handle)).toBe(added_line);
            expect(getLineOfHandle(opposite_left_handle)).toBe(opposite_line);
        });

        it("Given the right handle of a deleted line, then it will return the opposite line (not the deleted line)", () => {
            const deleted_line = FileLineStub.buildRemovedLine(1, 1);
            const opposite_line = FileLineStub.buildAddedLine(2, 1);
            const opposite_left_handle = {};
            const opposite_right_handle = {};
            const deleted_handle = { a: "a" };
            const added_group = GroupOfLinesStub.buildGroupOfAddedLines([opposite_line]);
            const deleted_group = GroupOfLinesStub.buildGroupOfRemovedLines([deleted_line]);

            initDataAndCodeMirrors(
                [opposite_line, deleted_line],
                left_code_mirror,
                right_code_mirror,
                side_by_side_line_grouper.withGroupsOfLines([added_group, deleted_group]),
                side_by_side_line_mapper.withSideBySideLineMap(
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
                )
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
