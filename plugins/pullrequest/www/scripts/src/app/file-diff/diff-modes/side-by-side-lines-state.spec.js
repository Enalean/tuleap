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
    initDataAndCodeMirrors,
    getCommentLine,
    getGroupLines
} from "./side-by-side-lines-state.js";
import { rewire$buildLineGroups, restore as restoreGrouper } from "./side-by-side-line-grouper.js";
import {
    rewire$buildLineToLineHandlesMap,
    restore as restoreMapper
} from "./side-by-side-line-mapper.js";

describe("side-by-side lines state", () => {
    let buildLineGroups, buildLineToLineHandlesMap, left_code_mirror, right_code_mirror;

    beforeEach(() => {
        buildLineGroups = jasmine.createSpy("buildLineGroups").and.returnValue({
            first_line_to_group_map: new Map(),
            line_to_group_map: new Map()
        });
        rewire$buildLineGroups(buildLineGroups);
        buildLineToLineHandlesMap = jasmine.createSpy("buildLineToLineHandlesMap");
        rewire$buildLineToLineHandlesMap(buildLineToLineHandlesMap);

        left_code_mirror = buildCodeMirrorSpy();
        right_code_mirror = buildCodeMirrorSpy();
    });

    afterEach(() => {
        restoreGrouper();
        restoreMapper();
    });

    describe("initDataAndCodeMirrors()", () => {
        it("Given diff lines, the left and right code mirrors, then it will store the lines, set the left and right code mirror content and build line maps", () => {
            const lines = [
                { old_offset: 1, new_offset: 1 },
                { old_offset: 2, new_offset: null },
                { old_offset: null, new_offset: 2 }
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
                unidiff_offset: 2
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
                unidiff_offsets: [2, 3]
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
});

function buildCodeMirrorSpy() {
    return jasmine.createSpyObj("code_mirror", ["setValue"]);
}
