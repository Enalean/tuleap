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

import {
    buildLineGroups,
    UNMOVED_GROUP,
    DELETED_GROUP,
    ADDED_GROUP
} from "./side-by-side-data-builder.js";

describe("Side-by-side data builder", () => {
    describe("buildLineGroups()", () => {
        it("Given diff lines, then it will return groups of deleted lines to be able to add line widgets", () => {
            const lines = [
                { unidiff_offset: 1, old_offset: 1, new_offset: 1 },
                { unidiff_offset: 2, old_offset: 2, new_offset: null },
                { unidiff_offset: 3, old_offset: 3, new_offset: null },
                { unidiff_offset: 4, old_offset: 4, new_offset: null },
                { unidiff_offset: 5, old_offset: 5, new_offset: 2 },
                { unidiff_offset: 6, old_offset: 6, new_offset: null },
                { unidiff_offset: 7, old_offset: 7, new_offset: null }
            ];

            const result = buildLineGroups(lines);

            expect(result.size).toEqual(4);
            const first_unmoved_group = result.get(1);
            const first_deleted_group = result.get(2);
            const second_unmoved_group = result.get(5);
            const second_deleted_group = result.get(6);

            expect(first_unmoved_group).toEqual({
                type: UNMOVED_GROUP,
                first_line_unidiff_offset: 1,
                height: 0
            });
            expect(first_deleted_group).toEqual({
                type: DELETED_GROUP,
                first_line_unidiff_offset: 2,
                height: 60
            });
            expect(second_unmoved_group).toEqual({
                type: UNMOVED_GROUP,
                first_line_unidiff_offset: 5,
                height: 20
            });
            expect(second_deleted_group).toEqual({
                type: DELETED_GROUP,
                first_line_unidiff_offset: 6,
                height: 40
            });
        });

        it("Given a deleted one-line file, then its group height will be 0. A line widget is not needed because CodeMirror always has an empty line", () => {
            const lines = [{ unidiff_offset: 1, old_offset: 1, new_offset: null }];

            const result = buildLineGroups(lines);

            expect(result.get(1)).toEqual({
                type: DELETED_GROUP,
                first_line_unidiff_offset: 1,
                height: 0
            });
        });

        it("Given an added one-line file, then its group height will be 0", () => {
            const lines = [{ unidiff_offset: 1, old_offset: null, new_offset: 1 }];

            const result = buildLineGroups(lines);

            expect(result.get(1)).toEqual({
                type: ADDED_GROUP,
                first_line_unidiff_offset: 1,
                height: 0
            });
        });

        it("Given diff lines, then it will return groups of added lines to be able to add line widgets", () => {
            const lines = [
                { unidiff_offset: 1, old_offset: 1, new_offset: 1 },
                { unidiff_offset: 2, old_offset: 2, new_offset: 2 },
                { unidiff_offset: 3, old_offset: null, new_offset: 3 },
                { unidiff_offset: 4, old_offset: null, new_offset: 4 },
                { unidiff_offset: 5, old_offset: null, new_offset: 5 },
                { unidiff_offset: 6, old_offset: 3, new_offset: 6 },
                { unidiff_offset: 7, old_offset: null, new_offset: 7 },
                { unidiff_offset: 8, old_offset: null, new_offset: 8 }
            ];

            const result = buildLineGroups(lines);

            expect(result.size).toEqual(4);
            const first_unmoved_group = result.get(1);
            const first_added_group = result.get(3);
            const second_unmoved_group = result.get(6);
            const second_added_group = result.get(7);

            expect(first_unmoved_group).toEqual({
                type: UNMOVED_GROUP,
                first_line_unidiff_offset: 1,
                height: 20
            });
            expect(first_added_group).toEqual({
                type: ADDED_GROUP,
                first_line_unidiff_offset: 3,
                height: 60
            });
            expect(second_unmoved_group).toEqual({
                type: UNMOVED_GROUP,
                first_line_unidiff_offset: 6,
                height: 20
            });
            expect(second_added_group).toEqual({
                type: ADDED_GROUP,
                first_line_unidiff_offset: 7,
                height: 40
            });
        });
    });
});
