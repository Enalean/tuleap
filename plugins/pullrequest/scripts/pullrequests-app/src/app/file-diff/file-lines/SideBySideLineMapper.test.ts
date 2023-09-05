/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { FileLineStub } from "../../../../tests/stubs/FileLineStub";
import { GroupOfLinesStub } from "../../../../tests/stubs/GroupOfLinesStub";
import { GroupSideBySideLinesStub } from "../../../../tests/stubs/GroupSideBySideLinesStub";
import type { Editor } from "codemirror";
import { SideBySideLineMapper } from "./SideBySideLineMapper";

type EditorWithLineHandles = Editor & {
    getLineHandle: jest.SpyInstance;
};

function buildCodeMirrorSpy(): EditorWithLineHandles {
    return {
        getLineHandle: jest.fn(),
    } as unknown as EditorWithLineHandles;
}

describe("side-by-side line mapper", () => {
    describe("buildLineToLineHandlesMap()", () => {
        let left_code_mirror: EditorWithLineHandles, right_code_mirror: EditorWithLineHandles;

        beforeEach(() => {
            left_code_mirror = buildCodeMirrorSpy();
            right_code_mirror = buildCodeMirrorSpy();

            left_code_mirror.getLineHandle.mockReturnValue("handle");
            right_code_mirror.getLineHandle.mockReturnValue("handle");
        });

        describe("Unmoved lines -", () => {
            it(`Given diff lines, a map from line to group and the left and right code mirrors,
                then it will return a map from line to left-side LineHandle and right-side LineHandle for unmoved lines`, () => {
                const first_unmoved_line = FileLineStub.buildUnMovedFileLine(1, 1, 1);
                const second_unmoved_line = FileLineStub.buildUnMovedFileLine(2, 2, 2);
                const lines_mapper = SideBySideLineMapper(
                    [first_unmoved_line, second_unmoved_line],
                    left_code_mirror,
                    right_code_mirror,
                );

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

                const map = lines_mapper.buildLineToLineHandlesMap(new Map());

                const first_actual = map.get(first_unmoved_line);
                expect(first_actual?.left_handle).toBe(first_line_left_handle);
                expect(first_actual?.right_handle).toBe(first_line_right_handle);
                const second_actual = map.get(second_unmoved_line);
                expect(second_actual?.left_handle).toBe(second_line_left_handle);
                expect(second_actual?.right_handle).toBe(second_line_right_handle);
            });
        });

        describe("Added lines -", () => {
            it(`will return a map from line to right-side LineHandle and on the left-side to the first line before the added group
                so that I can place a line widget there`, () => {
                const first_line = FileLineStub.buildUnMovedFileLine(1, 1, 1);
                const first_added_line = FileLineStub.buildAddedLine(2, 2);
                const second_added_line = FileLineStub.buildAddedLine(3, 3);

                const lines_mapper = SideBySideLineMapper(
                    [first_line, first_added_line, second_added_line],
                    left_code_mirror,
                    right_code_mirror,
                );

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

                const added_group = GroupOfLinesStub.buildGroupOfAddedLines([
                    first_added_line,
                    second_added_line,
                ]);
                const line_to_group_map = GroupSideBySideLinesStub()
                    .withGroupsOfLines([added_group])
                    .buildLineToGroupMap();

                const map = lines_mapper.buildLineToLineHandlesMap(line_to_group_map);

                const first_actual = map.get(first_added_line);
                expect(first_actual?.left_handle).toBe(first_line_before_group_left_handle);
                expect(first_actual?.right_handle).toBe(first_added_line_right_handle);
                const second_actual = map.get(second_added_line);
                expect(second_actual?.left_handle).toBe(first_line_before_group_left_handle);
                expect(second_actual?.right_handle).toBe(second_added_line_right_handle);
            });

            it(`Given the added group starts at the beginning of the file,
                then the left-side LineHandle will be at the start of the file (line 0)`, () => {
                const first_added_line = FileLineStub.buildAddedLine(1, 1);
                const second_added_line = FileLineStub.buildAddedLine(2, 2);

                const lines_mapper = SideBySideLineMapper(
                    [first_added_line, second_added_line],
                    left_code_mirror,
                    right_code_mirror,
                );

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

                const added_group = GroupOfLinesStub.buildGroupOfAddedLines([
                    first_added_line,
                    second_added_line,
                ]);
                const line_to_group_map = GroupSideBySideLinesStub()
                    .withGroupsOfLines([added_group])
                    .buildLineToGroupMap();

                const map = lines_mapper.buildLineToLineHandlesMap(line_to_group_map);

                const first_actual = map.get(first_added_line);
                expect(first_actual?.left_handle).toBe(first_line_left_handle);
                expect(first_actual?.right_handle).toBe(first_line_right_handle);
                const second_actual = map.get(second_added_line);
                expect(second_actual?.left_handle).toBe(first_line_left_handle);
                expect(second_actual?.right_handle).toBe(second_line_right_handle);
            });
        });

        describe("Deleted lines -", () => {
            it(`will return a map from line to left-side LineHandle and on the right-side to the first line after the deleted group
                so that I can place a line widget there`, () => {
                const first_line = FileLineStub.buildUnMovedFileLine(1, 1, 1);
                const second_deleted_line = FileLineStub.buildRemovedLine(2, 2);
                const third_deleted_line = FileLineStub.buildRemovedLine(3, 3);
                const fourth_line = FileLineStub.buildUnMovedFileLine(4, 2, 4);

                const lines_mapper = SideBySideLineMapper(
                    [first_line, second_deleted_line, third_deleted_line, fourth_line],
                    left_code_mirror,
                    right_code_mirror,
                );

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

                const deleted_group = GroupOfLinesStub.buildGroupOfRemovedLines([
                    second_deleted_line,
                    third_deleted_line,
                ]);
                const line_to_group_map = GroupSideBySideLinesStub()
                    .withGroupsOfLines([deleted_group])
                    .buildLineToGroupMap();

                const map = lines_mapper.buildLineToLineHandlesMap(line_to_group_map);

                const first_actual = map.get(second_deleted_line);
                expect(first_actual?.left_handle).toBe(second_deleted_line_left_handle);
                expect(first_actual?.right_handle).toBe(first_line_after_group_right_handle);
                const second_actual = map.get(third_deleted_line);
                expect(second_actual?.left_handle).toBe(third_deleted_line_left_handle);
                expect(second_actual?.right_handle).toBe(first_line_after_group_right_handle);
            });

            it(`Given the deleted group is at the end of the file,
                then the right-side LineHandle will be at the last line of the previous group`, () => {
                const first_unmoved_line = FileLineStub.buildUnMovedFileLine(1, 1, 1);
                const second_unmoved_line = FileLineStub.buildUnMovedFileLine(2, 2, 2);
                const third_deleted_line = FileLineStub.buildRemovedLine(3, 3);
                const fourth_deleted_line = FileLineStub.buildRemovedLine(4, 4);

                const lines_mapper = SideBySideLineMapper(
                    [
                        first_unmoved_line,
                        second_unmoved_line,
                        third_deleted_line,
                        fourth_deleted_line,
                    ],
                    left_code_mirror,
                    right_code_mirror,
                );

                const second_line_right_handle = {};
                const third_line_left_handle = {};
                const fourth_line_left_handle = {};
                left_code_mirror.getLineHandle.mockImplementation((index) => {
                    if (index === 0 || index === 1) {
                        return {};
                    }
                    if (index === 2) {
                        return third_line_left_handle;
                    }
                    if (index === 3) {
                        return fourth_line_left_handle;
                    }
                    throw new Error(index);
                });
                right_code_mirror.getLineHandle.mockImplementation((index) => {
                    if (index === 0) {
                        return {};
                    }
                    if (index === 1) {
                        return second_line_right_handle;
                    }
                    throw new Error(index);
                });

                const deleted_group = GroupOfLinesStub.buildGroupOfRemovedLines([
                    third_deleted_line,
                    fourth_deleted_line,
                ]);
                const line_to_group_map = GroupSideBySideLinesStub()
                    .withGroupsOfLines([deleted_group])
                    .buildLineToGroupMap();

                const map = lines_mapper.buildLineToLineHandlesMap(line_to_group_map);

                const first_actual = map.get(third_deleted_line);
                expect(first_actual?.left_handle).toBe(third_line_left_handle);
                expect(first_actual?.right_handle).toBe(second_line_right_handle);
                const second_actual = map.get(fourth_deleted_line);
                expect(second_actual?.left_handle).toBe(fourth_line_left_handle);
                expect(second_actual?.right_handle).toBe(second_line_right_handle);
            });

            it(`Given we're dealing with a deleted file, then the right-side LineHandle will be at the start of the file (line 0)`, () => {
                const first_deleted_line = FileLineStub.buildRemovedLine(1, 1);
                const second_deleted_line = FileLineStub.buildRemovedLine(2, 2);

                const lines_mapper = SideBySideLineMapper(
                    [first_deleted_line, second_deleted_line],
                    left_code_mirror,
                    right_code_mirror,
                );

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

                const deleted_group = GroupOfLinesStub.buildGroupOfRemovedLines([
                    first_deleted_line,
                    second_deleted_line,
                ]);
                const line_to_group_map = GroupSideBySideLinesStub()
                    .withGroupsOfLines([deleted_group])
                    .buildLineToGroupMap();

                const map = lines_mapper.buildLineToLineHandlesMap(line_to_group_map);

                const first_actual = map.get(first_deleted_line);
                expect(first_actual?.left_handle).toBe(first_line_left_handle);
                expect(first_actual?.right_handle).toBe(first_line_right_handle);
                const second_actual = map.get(second_deleted_line);
                expect(second_actual?.left_handle).toBe(second_line_left_handle);
                expect(second_actual?.right_handle).toBe(first_line_right_handle);
            });
        });

        describe(`Modified lines -`, () => {
            it(`Given the modified line is at the beginning of the file,
                then the first deleted line will match the first added line and vice-versa`, () => {
                const first_deleted_line = FileLineStub.buildRemovedLine(1, 1);
                const second_added_line = FileLineStub.buildAddedLine(2, 1);

                const lines_mapper = SideBySideLineMapper(
                    [first_deleted_line, second_added_line],
                    left_code_mirror,
                    right_code_mirror,
                );

                const first_line_left_handle = {};
                const second_line_right_handle = {};
                left_code_mirror.getLineHandle.mockImplementation((index) => {
                    if (index === 0) {
                        return first_line_left_handle;
                    }
                    throw new Error(index);
                });
                right_code_mirror.getLineHandle.mockImplementation((index) => {
                    if (index === 0) {
                        return second_line_right_handle;
                    }
                    throw new Error(index);
                });
                const deleted_group = GroupOfLinesStub.buildGroupOfRemovedLines([
                    first_deleted_line,
                ]);
                const added_group = GroupOfLinesStub.buildGroupOfAddedLines([second_added_line]);
                const line_to_group_map = GroupSideBySideLinesStub()
                    .withGroupsOfLines([deleted_group, added_group])
                    .buildLineToGroupMap();

                const map = lines_mapper.buildLineToLineHandlesMap(line_to_group_map);

                const first_actual = map.get(first_deleted_line);
                expect(first_actual?.left_handle).toBe(first_line_left_handle);
                expect(first_actual?.right_handle).toBe(second_line_right_handle);
                const second_actual = map.get(second_added_line);
                expect(second_actual?.left_handle).toBe(first_line_left_handle);
                expect(second_actual?.right_handle).toBe(second_line_right_handle);
            });
        });
    });
});
