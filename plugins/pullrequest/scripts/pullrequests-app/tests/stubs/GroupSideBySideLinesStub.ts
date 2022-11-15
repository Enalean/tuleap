/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { GroupSideBySideLines } from "../../src/app/file-diff/diff-modes/side-by-side-line-grouper";
import type { GroupOfLines } from "../../src/app/file-diff/diff-modes/types";

interface StubGroupSideBySideLines {
    hasBuiltLineToGroupMap: () => boolean;
    hasBuildFirstLineToGroupMap: () => boolean;
    withGroupsOfLines: (groups_of_lines: GroupOfLines[]) => GroupSideBySideLines;
    withEmptyLineToGroupMap: () => GroupSideBySideLines;
}

export const GroupSideBySideLinesStub = (): StubGroupSideBySideLines => {
    let has_built_line_to_group_map = false,
        has_built_first_line_to_group_map = false;

    const buildLineToGroupMap = (groups_of_lines: GroupOfLines[]): Map<number, GroupOfLines> => {
        const map = groups_of_lines.reduce((map, group) => {
            group.unidiff_offsets.forEach((line_unidiff_offset) => {
                map.set(line_unidiff_offset, group);
            });

            return map;
        }, new Map<number, GroupOfLines>());

        has_built_line_to_group_map = true;

        return map;
    };

    const buildFirstLineToGroupMap = (): Map<number, GroupOfLines> => {
        has_built_first_line_to_group_map = true;

        return new Map();
    };

    return {
        withGroupsOfLines: (groups_of_lines: GroupOfLines[]): GroupSideBySideLines => ({
            buildLineToGroupMap: () => buildLineToGroupMap(groups_of_lines),
            buildFirstLineToGroupMap: () => buildFirstLineToGroupMap(),
        }),
        withEmptyLineToGroupMap: (): GroupSideBySideLines => ({
            buildLineToGroupMap: () => buildLineToGroupMap([]),
            buildFirstLineToGroupMap: () => buildFirstLineToGroupMap(),
        }),
        hasBuiltLineToGroupMap: (): boolean => has_built_line_to_group_map,
        hasBuildFirstLineToGroupMap: (): boolean => has_built_first_line_to_group_map,
    };
};
