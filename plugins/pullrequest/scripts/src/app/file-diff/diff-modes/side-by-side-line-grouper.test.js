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
    buildLineGroups,
    UNMOVED_GROUP,
    DELETED_GROUP,
    ADDED_GROUP,
} from "./side-by-side-line-grouper.js";

describe("Side-by-side line grouper", () => {
    describe("buildLineGroups()", () => {
        describe("Deleted lines", () => {
            const lines = [
                { unidiff_offset: 1, old_offset: 1, new_offset: 1 },
                { unidiff_offset: 2, old_offset: 2, new_offset: null },
                { unidiff_offset: 3, old_offset: 3, new_offset: null },
                { unidiff_offset: 4, old_offset: 4, new_offset: null },
                { unidiff_offset: 5, old_offset: 5, new_offset: 2 },
                { unidiff_offset: 6, old_offset: 6, new_offset: null },
                { unidiff_offset: 7, old_offset: 7, new_offset: null },
            ];

            it("Given diff lines, then it will return groups of deleted lines and a map from first line to group to be able to place line widgets", () => {
                const { first_line_to_group_map } = buildLineGroups(lines);

                expect(first_line_to_group_map.size).toEqual(4);
                const first_unmoved_group = first_line_to_group_map.get(1);
                const first_deleted_group = first_line_to_group_map.get(2);
                const second_unmoved_group = first_line_to_group_map.get(5);
                const second_deleted_group = first_line_to_group_map.get(6);

                expect(first_unmoved_group).toEqual({
                    type: UNMOVED_GROUP,
                    unidiff_offsets: [1],
                });
                expect(first_deleted_group).toEqual({
                    type: DELETED_GROUP,
                    unidiff_offsets: [2, 3, 4],
                });
                expect(second_unmoved_group).toEqual({
                    type: UNMOVED_GROUP,
                    unidiff_offsets: [5],
                });
                expect(second_deleted_group).toEqual({
                    type: DELETED_GROUP,
                    unidiff_offsets: [6, 7],
                });
            });

            it("Given diff lines, then it will return groups of deleted lines and a map from each line to its group to be able to deal with comment's heights", () => {
                const { first_line_to_group_map, line_to_group_map } = buildLineGroups(lines);

                const first_unmoved_group = first_line_to_group_map.get(1);
                const first_deleted_group = first_line_to_group_map.get(2);
                const second_unmoved_group = first_line_to_group_map.get(5);
                const second_deleted_group = first_line_to_group_map.get(6);

                expect(line_to_group_map.size).toEqual(7);
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
            const lines = [
                { unidiff_offset: 1, old_offset: 1, new_offset: 1 },
                { unidiff_offset: 2, old_offset: 2, new_offset: 2 },
                { unidiff_offset: 3, old_offset: null, new_offset: 3 },
                { unidiff_offset: 4, old_offset: null, new_offset: 4 },
                { unidiff_offset: 5, old_offset: null, new_offset: 5 },
                { unidiff_offset: 6, old_offset: 3, new_offset: 6 },
                { unidiff_offset: 7, old_offset: null, new_offset: 7 },
                { unidiff_offset: 8, old_offset: null, new_offset: 8 },
            ];

            it("Given diff lines, then it will return groups of added lines to be able to add line widgets", () => {
                const { first_line_to_group_map } = buildLineGroups(lines);

                expect(first_line_to_group_map.size).toEqual(4);
                const first_unmoved_group = first_line_to_group_map.get(1);
                const first_added_group = first_line_to_group_map.get(3);
                const second_unmoved_group = first_line_to_group_map.get(6);
                const second_added_group = first_line_to_group_map.get(7);

                expect(first_unmoved_group).toEqual({
                    type: UNMOVED_GROUP,
                    unidiff_offsets: [1, 2],
                });
                expect(first_added_group).toEqual({
                    type: ADDED_GROUP,
                    unidiff_offsets: [3, 4, 5],
                });
                expect(second_unmoved_group).toEqual({
                    type: UNMOVED_GROUP,
                    unidiff_offsets: [6],
                });
                expect(second_added_group).toEqual({
                    type: ADDED_GROUP,
                    unidiff_offsets: [7, 8],
                });
            });

            it("Given diff lines, then it will return groups of added lines and a map from each line to its group to be able to deal with comment's heights", () => {
                const { first_line_to_group_map, line_to_group_map } = buildLineGroups(lines);

                const first_unmoved_group = first_line_to_group_map.get(1);
                const first_added_group = first_line_to_group_map.get(3);
                const second_unmoved_group = first_line_to_group_map.get(6);
                const second_added_group = first_line_to_group_map.get(7);

                expect(line_to_group_map.size).toEqual(8);
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
