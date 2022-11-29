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

import { UNMOVED_GROUP, DELETED_GROUP, ADDED_GROUP } from "./types";
import type { GroupSideBySideLines } from "./side-by-side-line-grouper";
import { SideBySideLineGrouper } from "./side-by-side-line-grouper";
import { FileLineStub } from "../../../../tests/stubs/FileLineStub";

describe("Side-by-side line grouper", () => {
    describe("buildLineGroups()", () => {
        describe("Deleted lines", () => {
            let line_grouper: GroupSideBySideLines;

            beforeEach(() => {
                line_grouper = SideBySideLineGrouper([
                    FileLineStub.buildUnMovedFileLine(1, 1, 1),
                    FileLineStub.buildRemovedLine(2, 2),
                    FileLineStub.buildRemovedLine(3, 3),
                    FileLineStub.buildRemovedLine(4, 4),
                    FileLineStub.buildUnMovedFileLine(5, 2, 5),
                    FileLineStub.buildRemovedLine(6, 6),
                    FileLineStub.buildRemovedLine(7, 7),
                ]);
            });

            it("Given diff lines, then it will return groups of deleted lines and a map from first line to group to be able to place line widgets", () => {
                const first_line_to_group_map = line_grouper.buildFirstLineToGroupMap();

                expect(first_line_to_group_map.size).toBe(4);
                const first_unmoved_group = first_line_to_group_map.get(1);
                const first_deleted_group = first_line_to_group_map.get(2);
                const second_unmoved_group = first_line_to_group_map.get(5);
                const second_deleted_group = first_line_to_group_map.get(6);

                expect(first_unmoved_group).toStrictEqual({
                    type: UNMOVED_GROUP,
                    unidiff_offsets: [1],
                });
                expect(first_deleted_group).toStrictEqual({
                    type: DELETED_GROUP,
                    unidiff_offsets: [2, 3, 4],
                });
                expect(second_unmoved_group).toStrictEqual({
                    type: UNMOVED_GROUP,
                    unidiff_offsets: [5],
                });
                expect(second_deleted_group).toStrictEqual({
                    type: DELETED_GROUP,
                    unidiff_offsets: [6, 7],
                });
            });

            it("Given diff lines, then it will return groups of deleted lines and a map from each line to its group to be able to deal with comment's heights", () => {
                const first_line_to_group_map = line_grouper.buildFirstLineToGroupMap();
                const line_to_group_map = line_grouper.buildLineToGroupMap();

                const first_unmoved_group = first_line_to_group_map.get(1);
                const first_deleted_group = first_line_to_group_map.get(2);
                const second_unmoved_group = first_line_to_group_map.get(5);
                const second_deleted_group = first_line_to_group_map.get(6);

                expect(line_to_group_map.size).toBe(7);
                expect(line_to_group_map.get(1)).toBe(first_unmoved_group);
                expect(line_to_group_map.get(2)).toBe(first_deleted_group);
                expect(line_to_group_map.get(3)).toBe(first_deleted_group);
                expect(line_to_group_map.get(4)).toBe(first_deleted_group);
                expect(line_to_group_map.get(5)).toBe(second_unmoved_group);
                expect(line_to_group_map.get(6)).toBe(second_deleted_group);
                expect(line_to_group_map.get(7)).toBe(second_deleted_group);
            });
        });

        describe("Added lines", () => {
            let line_grouper: GroupSideBySideLines;

            beforeEach(() => {
                line_grouper = SideBySideLineGrouper([
                    FileLineStub.buildUnMovedFileLine(1, 1, 1),
                    FileLineStub.buildUnMovedFileLine(2, 2, 2),
                    FileLineStub.buildAddedLine(3, 3),
                    FileLineStub.buildAddedLine(4, 4),
                    FileLineStub.buildAddedLine(5, 5),
                    FileLineStub.buildUnMovedFileLine(6, 6, 3),
                    FileLineStub.buildAddedLine(7, 7),
                    FileLineStub.buildAddedLine(8, 8),
                ]);
            });

            it("Given diff lines, then it will return groups of added lines to be able to add line widgets", () => {
                const first_line_to_group_map = line_grouper.buildFirstLineToGroupMap();

                expect(first_line_to_group_map.size).toBe(4);
                const first_unmoved_group = first_line_to_group_map.get(1);
                const first_added_group = first_line_to_group_map.get(3);
                const second_unmoved_group = first_line_to_group_map.get(6);
                const second_added_group = first_line_to_group_map.get(7);

                expect(first_unmoved_group).toStrictEqual({
                    type: UNMOVED_GROUP,
                    unidiff_offsets: [1, 2],
                });
                expect(first_added_group).toStrictEqual({
                    type: ADDED_GROUP,
                    unidiff_offsets: [3, 4, 5],
                });
                expect(second_unmoved_group).toStrictEqual({
                    type: UNMOVED_GROUP,
                    unidiff_offsets: [6],
                });
                expect(second_added_group).toStrictEqual({
                    type: ADDED_GROUP,
                    unidiff_offsets: [7, 8],
                });
            });

            it("Given diff lines, then it will return groups of added lines and a map from each line to its group to be able to deal with comment's heights", () => {
                const first_line_to_group_map = line_grouper.buildFirstLineToGroupMap();
                const line_to_group_map = line_grouper.buildLineToGroupMap();

                const first_unmoved_group = first_line_to_group_map.get(1);
                const first_added_group = first_line_to_group_map.get(3);
                const second_unmoved_group = first_line_to_group_map.get(6);
                const second_added_group = first_line_to_group_map.get(7);

                expect(line_to_group_map.size).toBe(8);
                expect(line_to_group_map.get(1)).toBe(first_unmoved_group);
                expect(line_to_group_map.get(2)).toBe(first_unmoved_group);
                expect(line_to_group_map.get(3)).toBe(first_added_group);
                expect(line_to_group_map.get(4)).toBe(first_added_group);
                expect(line_to_group_map.get(5)).toBe(first_added_group);
                expect(line_to_group_map.get(6)).toBe(second_unmoved_group);
                expect(line_to_group_map.get(7)).toBe(second_added_group);
                expect(line_to_group_map.get(8)).toBe(second_added_group);
            });
        });
    });
});
